import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";
import fs from "node:fs/promises";

const inputPath = "C:/Users/USER/Downloads/UAT_Ayolinx_ASPI - Johen Gaming.xlsx";
const input = await FileBlob.load(inputPath);
const workbook = await SpreadsheetFile.importXlsx(input);

const sheets = await workbook.inspect({
  kind: "sheet",
  include: "id,name",
  maxChars: 12000,
});
console.log("===SHEETS===");
console.log(sheets.ndjson);

const overview = await workbook.inspect({
  kind: "workbook,sheet,table,definedName,drawing",
  maxChars: 20000,
  tableMaxRows: 12,
  tableMaxCols: 12,
  tableMaxCellChars: 160,
});
console.log("===OVERVIEW===");
console.log(overview.ndjson);

await fs.mkdir("renders", { recursive: true });
const renderRanges = {
  "Transfer VA": "A1:H19",
  "QR MPM": "A1:H23",
  "Directdebit": "A1:H29",
  "Direct Debit": "A1:H25",
};
for (const [sheetName, range] of Object.entries(renderRanges)) {
  const preview = await workbook.render({
    sheetName,
    range,
    scale: 0.25,
    format: "png",
  });
  const safeName = sheetName.replaceAll(" ", "_");
  await fs.writeFile(`renders/${safeName}.png`, new Uint8Array(await preview.arrayBuffer()));
}
