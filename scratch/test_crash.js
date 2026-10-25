const count = 0;
const firstCase = null;
try {
  const html = `<div onclick="if (${count} > 0) selectInvoiceById(${firstCase.id}, false);"></div>`;
  console.log('Success:', html);
} catch (e) {
  console.log('CRASHED WITH ERROR:', e.message);
}
