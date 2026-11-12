import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const outputPath = "C:/Kerjaan-Marshall/johen-marketplace/outputs/uat-ayolinx-20261109/UAT_Ayolinx_ASPI - Johen Gaming - Hasil UAT.xlsx";
const workbook = await SpreadsheetFile.importXlsx(await FileBlob.load(outputPath));

const checks = [
  ["Transfer VA", "E10:H19"],
  ["QR MPM", "E10:H23"],
  ["Directdebit", "E10:H29"],
  ["Direct Debit", "E10:H25"],
];

for (const [sheetName, rangeAddress] of checks) {
  const values = workbook.worksheets.getItem(sheetName).getRange(rangeAddress).values;
  const blanks = values.flat().filter((value) => value === null || value === "").length;
  const statuses = values.map((row) => row[2]);
  const counts = Object.fromEntries([...new Set(statuses)].map((status) => [status, statuses.filter((value) => value === status).length]));
  console.log(`${sheetName}|blanks=${blanks}|statuses=${JSON.stringify(counts)}`);
}

const errors = await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!",
  options: { useRegex: true, maxResults: 300 },
  maxChars: 5000,
});
console.log(errors.ndjson);
