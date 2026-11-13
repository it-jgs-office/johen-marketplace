import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const inputPath = "C:/Kerjaan-Marshall/johen-marketplace/outputs/uat-ayolinx-20261112/UAT_Ayolinx_ASPI - Johen Gaming - Hardcode VA 11.10-11.12.xlsx";
const resultsPath = "C:/Kerjaan-Marshall/johen-marketplace/.codex_tmp/ayolinx_uat/uat-results.json";
const outputDir = "C:/Kerjaan-Marshall/johen-marketplace/outputs/uat-ayolinx-20261113";
const outputPath = `${outputDir}/UAT_Ayolinx_ASPI - Johen Gaming - Retest ALTO_QRIS.xlsx`;
const previewDir = "C:/Kerjaan-Marshall/johen-marketplace/tmp/uat-problems/previews";
const mode = process.argv[2] ?? "inspect";

const results = JSON.parse(await fs.readFile(resultsPath, "utf8"));
const input = await FileBlob.load(inputPath);
const workbook = await SpreadsheetFile.importXlsx(input);
const sheet = workbook.worksheets.getItem("QR MPM");

async function renderPreview(filename) {
  await fs.mkdir(previewDir, { recursive: true });
  const preview = await workbook.render({
    sheetName: "QR MPM",
    range: "A1:H23",
    scale: 0.5,
    format: "png",
  });
  await fs.writeFile(`${previewDir}/${filename}`, new Uint8Array(await preview.arrayBuffer()));
}

async function inspectQr(label) {
  const check = await workbook.inspect({
    kind: "table",
    range: "'QR MPM'!A1:H23",
    include: "values,formulas",
    tableMaxRows: 30,
    tableMaxCols: 8,
    tableMaxCellChars: 220,
    maxChars: 22000,
  });
  console.log(label);
  console.log(check.ndjson);
}

if (mode === "inspect") {
  await inspectQr("BEFORE");
  await renderPreview("before-alto-qris.png");
  process.exit(0);
}

if (mode !== "edit") throw new Error(`Unknown mode: ${mode}`);

const formatJson = (value) => JSON.stringify(value, null, 2);

function formatRequest(entry) {
  if (!entry.executed) return "Tidak dijalankan";
  return [
    "URL Endpoint:",
    `${entry.request.method} ${entry.request.url}`,
    "",
    "Header Request:",
    ...Object.entries(entry.request.headers).map(([key, value]) => `${key}: ${value}`),
    "",
    "Request Body:",
    formatJson(entry.request.body),
  ].join("\n");
}

function formatResponse(entry) {
  if (!entry.executed) return "Tidak tersedia";
  if (entry.response.error) return `Request Error:\n${entry.response.error}`;
  return [
    `HTTP Status: ${entry.response.http_status}`,
    "Response Body:",
    formatJson(entry.response.body),
  ].join("\n");
}

const evaluations = {
  "18.1": ["PASS", "Retest 13/11/2026 dengan ALTO_QRIS: aktual 4014701 Access Token Invalid sesuai expected 401xx01."],
  "18.2": ["PASS", "Retest 13/11/2026 dengan ALTO_QRIS: aktual 4014700 Unauthorized Signature sesuai expected 401xx00."],
  "18.3": ["PASS", "Retest 13/11/2026 dengan ALTO_QRIS: aktual 4004702 Invalid Mandatory Field partnerReferenceNo sesuai expected 400xx02."],
  "18.4": ["FAIL", "Retest 13/11/2026 dengan ALTO_QRIS: aktual 5002502 Channel Error; expected 400xx01 Invalid Field Format."],
  "18.5": ["PASS", "Retest 13/11/2026 dengan ALTO_QRIS: aktual 4094700 Conflict sesuai expected 409xx00."],
  "18.6": ["FAIL", "Retest 13/11/2026 dengan ALTO_QRIS: generate QR valid menghasilkan 5002502 Channel Error; expected 2004700 Successful."],
  "18.7": ["FAIL", "Retest 13/11/2026 dengan ALTO_QRIS: aktual 4014701 Access Token Invalid; expected 4044708 Invalid Merchant."],
  "18.15": ["BLOCKED", "Retest ALTO_QRIS belum dapat menghasilkan transaksi sukses karena generate QR valid mendapat 5002502 Channel Error. Simulasi pembayaran juga memerlukan Mark Success di dashboard Ayolinx."],
  "18.16": ["FAIL", "Retest 13/11/2026 dengan ALTO_QRIS: query menghasilkan 5005503 Query Transaction Not Found karena generate QR gagal; expected 2005100 dengan status 03 Pending."],
  "18.17": ["FAIL", "Retest 13/11/2026 dengan ALTO_QRIS: aktual 5005503 Query Transaction Not Found; expected 4045101 Transaction Not Found."],
  "18.18": ["BLOCKED", "Callback transaksi sukses belum dapat diuji karena generate QR valid mendapat 5002502 Channel Error dan endpoint inbound /v1.0/qr/qr-mpm-notify belum tersedia."],
  "18.19": ["BLOCKED", "Callback transaksi gagal belum dapat diuji karena endpoint inbound /v1.0/qr/qr-mpm-notify belum tersedia."],
  "18.20": ["FAIL", "Retest 13/11/2026 dengan ALTO_QRIS: cancel menghasilkan 5005203 Query Transaction Not Found; expected 2007700 Successful."],
  "18.21": ["FAIL", "Retest 13/11/2026 dengan ALTO_QRIS: cancel ulang menghasilkan 5005203 Query Transaction Not Found; expected 2027700 Request In Progress."],
};

const caseIds = [
  "18.1", "18.2", "18.3", "18.4", "18.5", "18.6", "18.7",
  "18.15", "18.16", "18.17", "18.18", "18.19", "18.20", "18.21",
];

const rows = caseIds.map((caseId) => {
  const entry = results.qr_mpm[caseId];
  if (!entry) throw new Error(`Missing QR UAT result ${caseId}`);
  const [status, note] = evaluations[caseId];
  return [formatRequest(entry), formatResponse(entry), status, note];
});

sheet.getRange("E10:H23").values = rows;
sheet.getRange("C6").values = [["09/11/2026 -- 13/11/2026"]];

workbook.recalculate();

const finalCheck = await workbook.inspect({
  kind: "table",
  range: "'QR MPM'!A9:H23",
  include: "values,formulas",
  tableMaxRows: 20,
  tableMaxCols: 8,
  tableMaxCellChars: 300,
  maxChars: 26000,
});
console.log("AFTER");
console.log(finalCheck.ndjson);

const errors = await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!",
  options: { useRegex: true, maxResults: 300 },
  summary: "final formula error scan",
});
console.log(errors.ndjson);

await renderPreview("after-alto-qris.png");
await fs.mkdir(outputDir, { recursive: true });
const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(outputPath);
console.log(outputPath);
