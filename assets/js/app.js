function confirmDelete(message) {
    return confirm(message || 'Yakin ingin menghapus data ini?');
}

function printPage() {
    window.print();
}

function addLoanRow() {
    const template = document.querySelector('#loan-row-template');
    const target = document.querySelector('#loan-items');
    if (!template || !target) return;
    const clone = template.content.cloneNode(true);
    target.appendChild(clone);
}

function removeLoanRow(button) {
    const rows = document.querySelectorAll('.loan-row');
    if (rows.length <= 1) return;
    button.closest('.loan-row').remove();
}
