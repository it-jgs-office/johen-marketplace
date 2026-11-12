import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const inputPath = "C:/Kerjaan-Marshall/johen-marketplace/outputs/uat-ayolinx-20261109/UAT_Ayolinx_ASPI - Johen Gaming - Hasil UAT - Revisi.xlsx";
const outputDir = "C:/Kerjaan-Marshall/johen-marketplace/outputs/uat-ayolinx-20261112";
const outputPath = `${outputDir}/UAT_Ayolinx_ASPI - Johen Gaming - Hardcode VA 11.10-11.12.xlsx`;
const previewDir = "C:/Kerjaan-Marshall/johen-marketplace/tmp/uat-problems/previews";
const mode = process.argv[2] ?? "inspect";

const input = await FileBlob.load(inputPath);
const workbook = await SpreadsheetFile.importXlsx(input);
const sheet = workbook.worksheets.getItem("Transfer VA");

async function renderPreview(filename) {
  await fs.mkdir(previewDir, { recursive: true });
  const preview = await workbook.render({
    sheetName: "Transfer VA",
    range: "A9:H19",
    scale: 1.25,
    format: "png",
  });
  await fs.writeFile(`${previewDir}/${filename}`, new Uint8Array(await preview.arrayBuffer()));
}

async function inspectTarget(label) {
  const inspection = await workbook.inspect({
    kind: "table",
    range: "'Transfer VA'!A9:H19",
    include: "values,formulas",
    tableMaxRows: 20,
    tableMaxCols: 8,
    maxChars: 16000,
  });
  console.log(label);
  console.log(inspection.ndjson);
}

if (mode === "inspect") {
  await inspectTarget("BEFORE");
  await renderPreview("before-va-hardcode.png");
  process.exit(0);
}

if (mode !== "edit") throw new Error(`Unknown mode: ${mode}`);

function requestText({ externalId, payload }) {
  return [
    "HARDCODE - contoh callback Ayolinx",
    "POST https://johengaming.store/v1.0/transfer-va/payment",
    "Content-Type: application/json",
    "Authorization: Bearer [REDACTED]",
    "X-TIMESTAMP: 2026-11-12T10:00:00+07:00",
    "X-SIGNATURE: [REDACTED]",
    "X-PARTNER-ID: [REDACTED]",
    `X-EXTERNAL-ID: ${externalId}`,
    "CHANNEL-ID: H2H",
    "",
    JSON.stringify(payload, null, 2),
  ].join("\n");
}

function responseText(httpStatus, payload) {
  return [
    "HARDCODE - contoh response merchant",
    `HTTP Status: ${httpStatus}`,
    "Content-Type: application/json",
    "",
    JSON.stringify(payload, null, 2),
  ].join("\n");
}

const commonPayload = {
  partnerServiceId: "98829172",
  customerNo: "30000000347246441952",
  virtualAccountName: "Johen UAT Customer",
  virtualAccountEmail: "uat@johengaming.store",
  virtualAccountPhone: "6281234567890",
  virtualAccountTrxType: "C",
  channelCode: 6014,
  trxDateTime: "2026-11-12T10:00:00+07:00",
  paymentType: 1,
  flagAdvise: "N",
  additionalInfo: { channel: "VIRTUAL_ACCOUNT_BNI" },
};

const validPayload = {
  ...commonPayload,
  virtualAccountNo: "9882107423127511",
  trxId: "920364045954",
  paymentRequestId: "uat-va-payment-valid-20261112",
  paidAmount: { value: "11500.00", currency: "IDR" },
  totalAmount: { value: "11500.00", currency: "IDR" },
  referenceNo: "202611120000001",
};

const notFoundPayload = {
  ...commonPayload,
  virtualAccountNo: "9882999999999999",
  trxId: "920364045955",
  paymentRequestId: "uat-va-payment-not-found-20261112",
  paidAmount: { value: "11500.00", currency: "IDR" },
  totalAmount: { value: "11500.00", currency: "IDR" },
  referenceNo: "202611120000002",
};

const invalidAmountPayload = {
  ...commonPayload,
  virtualAccountNo: "9882107423127511",
  trxId: "920364045954",
  paymentRequestId: "uat-va-payment-invalid-amount-20261112",
  paidAmount: { value: "10000.00", currency: "IDR" },
  totalAmount: { value: "11500.00", currency: "IDR" },
  referenceNo: "202611120000003",
};

const validResponse = {
  responseCode: "2002500",
  responseMessage: "Successful",
  virtualAccountData: {
    partnerServiceId: validPayload.partnerServiceId,
    customerNo: validPayload.customerNo,
    virtualAccountNo: validPayload.virtualAccountNo,
    virtualAccountName: validPayload.virtualAccountName,
    virtualAccountTrxType: validPayload.virtualAccountTrxType,
    trxId: validPayload.trxId,
    totalAmount: validPayload.totalAmount,
    paymentRequestId: validPayload.paymentRequestId,
    paidAmount: validPayload.paidAmount,
    billDetails: [],
    additionalInfo: validPayload.additionalInfo,
    paymentFlagReason: { english: "Success", indonesia: "Sukses" },
  },
};

sheet.getRange("E15:H17").values = [
  [
    requestText({ externalId: "uat-va-1110-20261112", payload: validPayload }),
    responseText(200, validResponse),
    "PASS",
    "HARDCODE sesuai permintaan Ayolinx untuk kelengkapan UAT. Bukan hasil callback aktual; validasi aktual menunggu callback resmi Ayolinx ke endpoint production.",
  ],
  [
    requestText({ externalId: "uat-va-1111-20261112", payload: notFoundPayload }),
    responseText(404, { responseCode: "4042512", responseMessage: "Bill not found" }),
    "PASS",
    "HARDCODE sesuai permintaan Ayolinx untuk kelengkapan UAT. Bukan hasil callback aktual; validasi aktual menunggu callback resmi Ayolinx ke endpoint production.",
  ],
  [
    requestText({ externalId: "uat-va-1112-20261112", payload: invalidAmountPayload }),
    responseText(404, { responseCode: "4042513", responseMessage: "Invalid Amount" }),
    "PASS",
    "HARDCODE sesuai permintaan Ayolinx untuk kelengkapan UAT. Bukan hasil callback aktual; validasi aktual menunggu callback resmi Ayolinx ke endpoint production.",
  ],
];

workbook.recalculate();

const targetCheck = await workbook.inspect({
  kind: "table",
  range: "'Transfer VA'!E15:H17",
  include: "values,formulas",
  tableMaxRows: 3,
  tableMaxCols: 4,
  maxChars: 16000,
});
console.log("AFTER");
console.log(targetCheck.ndjson);

const errors = await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!",
  options: { useRegex: true, maxResults: 100 },
  summary: "final formula error scan",
});
console.log(errors.ndjson);

await renderPreview("after-va-hardcode.png");
await fs.mkdir(outputDir, { recursive: true });
const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(outputPath);
console.log(outputPath);
