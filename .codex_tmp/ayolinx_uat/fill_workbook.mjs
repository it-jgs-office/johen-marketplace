import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const inputPath = "C:/Users/USER/Downloads/UAT_Ayolinx_ASPI - Johen Gaming.xlsx";
const resultsPath = "C:/Kerjaan-Marshall/johen-marketplace/.codex_tmp/ayolinx_uat/uat-results.json";
const outputDir = "C:/Kerjaan-Marshall/johen-marketplace/outputs/uat-ayolinx-20261109";
const outputPath = `${outputDir}/UAT_Ayolinx_ASPI - Johen Gaming - Hasil UAT.xlsx`;

const results = JSON.parse(await fs.readFile(resultsPath, "utf8"));
const input = await FileBlob.load(inputPath);
const workbook = await SpreadsheetFile.importXlsx(input);

const formatJson = (value) => JSON.stringify(value, null, 2);

function formatRequest(entry) {
  if (!entry.executed) return "Tidak dijalankan";
  const request = entry.request;
  return [
    "URL Endpoint:",
    `${request.method} ${request.url}`,
    "",
    "Header Request:",
    ...Object.entries(request.headers).map(([key, value]) => `${key}: ${value}`),
    "",
    "Request Body:",
    formatJson(request.body),
  ].join("\n");
}

function formatResponse(entry) {
  if (!entry.executed) return "Tidak tersedia";
  if (entry.response.error) {
    return `Request Error:\n${entry.response.error}`;
  }
  return [
    `HTTP Status: ${entry.response.http_status}`,
    "Response Body:",
    formatJson(entry.response.body),
  ].join("\n");
}

const blockedTranslation = {
  "11.10": "Endpoint inbound VA /v1.0/transfer-va/payment belum tersedia pada aplikasi.",
  "11.11": "Endpoint inbound VA belum tersedia sehingga VA tidak terdaftar belum dapat diuji.",
  "11.12": "Endpoint inbound VA belum tersedia sehingga invalid amount belum dapat diuji.",
  "18.15": "Transaksi sukses memerlukan simulasi Mark Success pada dashboard merchant Ayolinx.",
  "18.18": "Endpoint inbound QR /v1.0/qr/qr-mpm-notify belum tersedia pada aplikasi.",
  "18.19": "Endpoint inbound QR /v1.0/qr/qr-mpm-notify belum tersedia pada aplikasi.",
};

const evaluations = {
  transfer_va: {
    "11.1": ["PASS", "Aktual 4012701 Access Token Invalid sesuai pola expected 401xx01."],
    "11.2": ["PASS", "Aktual 4012700 Unauthorized Signature sesuai pola expected 401xx00."],
    "11.3": ["PASS", "Aktual 4002702 Invalid Mandatory Field customerNo sesuai pola expected 400xx02."],
    "11.4": ["FAIL", "Aktual 5002501 Internal Server Error; expected 400xx01 Invalid Field Format."],
    "11.5": ["PASS", "Aktual 4092700 Conflict sesuai pola expected 409xx00."],
    "11.14": ["PASS", "VA berhasil dibuat dengan responseCode 2002700."],
    "11.17": ["FAIL", "Aktual 2002800 Success dengan status 03; template mengharapkan 2003000. Dokumentasi terbaru memakai 2002800 untuk Inquiry VA."],
  },
  qr_mpm: {
    "18.1": ["PASS", "Aktual 4014701 Access Token Invalid sesuai pola expected 401xx01."],
    "18.2": ["PASS", "Aktual 4014700 Unauthorized Signature sesuai pola expected 401xx00."],
    "18.3": ["PASS", "Aktual 4004702 Invalid Mandatory Field partnerReferenceNo sesuai pola expected 400xx02."],
    "18.4": ["FAIL", "Aktual 5002501 Internal Server Error; expected 400xx01 Invalid Field Format."],
    "18.5": ["PASS", "Aktual 4094700 Conflict sesuai pola expected 409xx00."],
    "18.6": ["FAIL", "Generate QR mengembalikan 5002501 Internal Server Error; query transaksi menunjukkan status 06 (Failed)."],
    "18.7": ["FAIL", "Aktual 4014701 Access Token Invalid; expected 4044708 Invalid Merchant."],
    "18.16": ["FAIL", "Aktual 2005100 tetapi latestTransactionStatus 06; expected status 03 (Pending)."],
    "18.17": ["FAIL", "Aktual 5005503 Query Transaction Not Found; expected 4045101 Transaction Not Found."],
    "18.20": ["FAIL", "Aktual 5005203 Query Transaction Not Found; expected 2007700 Successful."],
    "18.21": ["FAIL", "Aktual 5005203 Query Transaction Not Found; expected 2027700 Request In Progress."],
  },
};

function rowFor(section, caseId) {
  const entry = results[section][caseId];
  if (!entry) throw new Error(`Missing UAT result ${section} ${caseId}`);
  if (!entry.executed) {
    const note = blockedTranslation[caseId] ?? "Dokumentasi Direct Debit, implementasi API, dan akun uji sandbox belum tersedia.";
    return [formatRequest(entry), formatResponse(entry), "BLOCKED", note];
  }
  const evaluation = evaluations[section]?.[caseId];
  if (!evaluation) throw new Error(`Missing evaluation ${section} ${caseId}`);
  return [formatRequest(entry), formatResponse(entry), evaluation[0], evaluation[1]];
}

const transferVa = workbook.worksheets.getItem("Transfer VA");
const transferCases = ["11.1", "11.2", "11.3", "11.4", "11.5", "11.10", "11.11", "11.12", "11.14", "11.17"];
transferVa.getRange("E10:H19").values = transferCases.map((caseId) => rowFor("transfer_va", caseId));

const qrMpm = workbook.worksheets.getItem("QR MPM");
const qrCases = ["18.1", "18.2", "18.3", "18.4", "18.5", "18.6", "18.7", "18.15", "18.16", "18.17", "18.18", "18.19", "18.20", "18.21"];
qrMpm.getRange("E10:H23").values = qrCases.map((caseId) => rowFor("qr_mpm", caseId));

const directDebitCases = [
  "19.1", "19.2", "19.3", "19.4", "19.5", "19.6", "19.7", "19.8", "19.9", "19.10",
  "19.11", "19.12", "19.13", "19.14", "19.15", "19.16", "19.17", "19.18", "19.19", "19.20",
];

const directDebitSource = workbook.worksheets.getItem("Directdebit");
directDebitSource.getRange("E10:H29").values = directDebitCases.map((caseId) => rowFor("direct_debit", caseId));

const directDebitCopy = workbook.worksheets.getItem("Direct Debit");
directDebitCopy.getRange("C3").values = directDebitSource.getRange("C3").values;
directDebitCopy.getRange("C5").values = directDebitSource.getRange("C5").values;
directDebitCopy.getRange("C6").values = directDebitSource.getRange("C6").values;
directDebitCopy.getRange("E10:H25").values = directDebitCases.slice(0, 16).map((caseId) => rowFor("direct_debit", caseId));

workbook.recalculate();

for (const [sheetName, range] of [
  ["Transfer VA", "A1:H19"],
  ["QR MPM", "A1:H23"],
  ["Directdebit", "A1:H29"],
  ["Direct Debit", "A1:H25"],
]) {
  const check = await workbook.inspect({
    kind: "table",
    sheetId: sheetName,
    range,
    include: "values,formulas",
    tableMaxRows: 40,
    tableMaxCols: 8,
    tableMaxCellChars: 180,
    maxChars: 30000,
  });
  console.log(`===CHECK ${sheetName}===`);
  console.log(check.ndjson);
}

const errors = await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!",
  options: { useRegex: true, maxResults: 300 },
  maxChars: 5000,
});
console.log("===FORMULA ERRORS===");
console.log(errors.ndjson);

await fs.mkdir(`${outputDir}/previews`, { recursive: true });
for (const [sheetName, range] of [
  ["Transfer VA", "A1:H19"],
  ["QR MPM", "A1:H23"],
  ["Directdebit", "A1:H29"],
  ["Direct Debit", "A1:H25"],
]) {
  const preview = await workbook.render({ sheetName, range, scale: 0.25, format: "png" });
  const safeName = sheetName.replaceAll(" ", "_");
  await fs.writeFile(`${outputDir}/previews/${safeName}.png`, new Uint8Array(await preview.arrayBuffer()));
}

await fs.mkdir(outputDir, { recursive: true });
const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(outputPath);
console.log(`OUTPUT=${outputPath}`);
