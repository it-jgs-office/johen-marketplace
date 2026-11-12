import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const inputPath = "C:/Kerjaan-Marshall/johen-marketplace/outputs/uat-ayolinx-20261109/UAT_Ayolinx_ASPI - Johen Gaming - Hasil UAT.xlsx";
const outputPath = "C:/Kerjaan-Marshall/johen-marketplace/outputs/uat-ayolinx-20261109/UAT_Ayolinx_ASPI - Johen Gaming - Hasil UAT - Revisi.xlsx";
const previewDir = "C:/Kerjaan-Marshall/johen-marketplace/tmp/uat-problems/previews";
const mode = process.argv[2] ?? "inspect";

const input = await FileBlob.load(inputPath);
const workbook = await SpreadsheetFile.importXlsx(input);

async function renderPreview(sheetName, range, filename) {
  await fs.mkdir(previewDir, { recursive: true });
  const preview = await workbook.render({ sheetName, range, scale: 1.25, format: "png" });
  await fs.writeFile(`${previewDir}/${filename}`, new Uint8Array(await preview.arrayBuffer()));
}

if (mode === "inspect") {
  const inspection = await workbook.inspect({
    kind: "table",
    range: "'Transfer VA'!A9:H19",
    include: "values,formulas",
    tableMaxRows: 20,
    tableMaxCols: 8,
    maxChars: 12000,
  });
  console.log(inspection.ndjson);
  await renderPreview("Transfer VA", "A9:H19", "before-transfer-va.png");
  await renderPreview("QR MPM", "A9:H23", "before-qr-mpm.png");
  await renderPreview("Directdebit", "A9:H29", "before-directdebit.png");
  process.exit(0);
}

if (mode !== "edit") throw new Error(`Unknown mode: ${mode}`);

const transferNotes = {
  H13: "PROBLEM: format amount tidak valid menghasilkan 5002501 Internal Server Error, bukan 400xx01 Invalid Field Format. Perlu perbaikan/konfirmasi Ayolinx.",
  H15: "BLOCKER: endpoint inbound merchant /v1.0/transfer-va/payment belum tersedia sehingga pembayaran VA valid belum dapat diuji.",
  H16: "BLOCKER: endpoint inbound merchant /v1.0/transfer-va/payment belum tersedia sehingga skenario VA tidak terdaftar belum dapat diuji.",
  H17: "BLOCKER: endpoint inbound merchant /v1.0/transfer-va/payment belum tersedia sehingga invalid amount belum dapat diuji.",
  H19: "PROBLEM TEMPLATE: hasil aktual 2002800 sesuai dokumentasi Ayolinx terbaru, tetapi template UAT meminta 2003000. Minta konfirmasi tertulis Ayolinx sebelum status diubah. Dokumen: https://doc.ayolinx.id/api-279112710",
};

const qrNotes = {
  H13: "PROBLEM: format amount tidak valid menghasilkan 5002501 Internal Server Error, bukan 400xx01 Invalid Field Format. Perlu perbaikan/konfirmasi Ayolinx.",
  H15: "PROBLEM: Generate QR valid menghasilkan 5002501 Internal Server Error; transaksi kemudian berstatus 06 (Failed).",
  H16: "PROBLEM: pengujian Invalid Merchant tidak terisolasi karena respons yang diterima 4014701 Access Token Invalid, bukan 4044708 Invalid Merchant.",
  H17: "BLOCKER: transaksi sukses memerlukan simulasi Mark Success pada dashboard merchant Ayolinx.",
  H18: "PROBLEM: query mengembalikan 2005100 tetapi latestTransactionStatus 06 (Failed), bukan 03 (Pending).",
  H19: "PROBLEM: transaksi tidak ditemukan menghasilkan 5005503 Query Transaction Not Found, bukan 4045101.",
  H20: "BLOCKER + PROBLEM TEMPLATE: endpoint inbound /v1.0/qr/qr-mpm-notify belum tersedia. Dokumentasi meminta respons 2005600, sedangkan template UAT meminta 2005200. Minta konfirmasi Ayolinx. Dokumen: https://doc.ayolinx.id/api-299374923",
  H21: "BLOCKER + PROBLEM TEMPLATE: endpoint inbound /v1.0/qr/qr-mpm-notify belum tersedia. Dokumentasi meminta respons 2005600, sedangkan template UAT meminta 2005200. Minta konfirmasi Ayolinx. Dokumen: https://doc.ayolinx.id/api-299374923",
  H22: "PROBLEM: cancel menghasilkan 5005203 Query Transaction Not Found, bukan 2007700 Successful.",
  H23: "PROBLEM: cancel menghasilkan 5005203 Query Transaction Not Found, bukan 2027700 Request In Progress.",
};

const directDebitNotes = {
  H10: "BLOCKER: API Direct Debit dan akun sandbox belum tersedia.",
  H11: "BLOCKER: API Direct Debit dan akun sandbox belum tersedia.",
  H12: "BLOCKER: API Direct Debit dan akun sandbox belum tersedia.",
  H13: "BLOCKER: API Direct Debit dan akun sandbox belum tersedia.",
  H14: "BLOCKER: API Direct Debit dan akun sandbox belum tersedia.",
  H15: "BLOCKER: API dan akun sandbox belum tersedia. Doc payment 2005400: https://doc.ayolinx.id/api-255569333",
  H16: "BLOCKER: butuh implementasi API dan akun uji sandbox.",
  H17: "BLOCKER: butuh implementasi API dan akun uji sandbox.",
  H18: "BLOCKER: butuh implementasi API dan akun uji sandbox.",
  H19: "BLOCKER: butuh implementasi API dan akun uji sandbox.",
  H20: "BLOCKER: butuh implementasi API dan akun uji sandbox.",
  H21: "BLOCKER: butuh implementasi API dan akun uji sandbox.",
  H22: "BLOCKER: endpoint Payment Status belum dikonfirmasi Ayolinx.",
  H23: "BLOCKER: endpoint Payment Status belum dikonfirmasi Ayolinx.",
  H24: "BLOCKER: callback belum tersedia. Doc notify 2005600: https://doc.ayolinx.id/api-255569334",
  H25: "BLOCKER: callback belum tersedia. Doc notify 2005600: https://doc.ayolinx.id/api-255569334",
  H26: "BLOCKER: endpoint Cancel belum dikonfirmasi Ayolinx.",
  H27: "BLOCKER: endpoint Cancel belum dikonfirmasi Ayolinx.",
  H28: "BLOCKER: endpoint Refund belum dikonfirmasi Ayolinx.",
  H29: "BLOCKER: endpoint Refund belum dikonfirmasi Ayolinx.",
};

function applyNotes(sheetName, notes) {
  const sheet = workbook.worksheets.getItem(sheetName);
  for (const [cell, value] of Object.entries(notes)) {
    sheet.getRange(cell).values = [[value]];
  }
}

applyNotes("Transfer VA", transferNotes);
applyNotes("QR MPM", qrNotes);
applyNotes("Directdebit", directDebitNotes);
applyNotes("Direct Debit", Object.fromEntries(Object.entries(directDebitNotes).filter(([cell]) => Number(cell.slice(1)) <= 25)));

workbook.recalculate();

const check = await workbook.inspect({
  kind: "table",
  range: "'QR MPM'!G13:H23",
  include: "values,formulas",
  tableMaxRows: 20,
  tableMaxCols: 2,
  maxChars: 10000,
});
console.log(check.ndjson);

const errors = await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!",
  options: { useRegex: true, maxResults: 100 },
  summary: "final formula error scan",
});
console.log(errors.ndjson);

await renderPreview("Transfer VA", "A9:H19", "after-transfer-va.png");
await renderPreview("QR MPM", "A9:H23", "after-qr-mpm.png");
await renderPreview("Directdebit", "A9:H29", "after-directdebit.png");

const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(outputPath);
console.log(outputPath);
