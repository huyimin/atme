document.getElementById('confirm-invoice-btn').addEventListener('click', function() {
    fetch('/index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'confirm_invoice' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Invoice confirmedsuccessfully!');
            // Redirect to orders page or wherever appropriate
            window.location.href = '/index.php?view=my_orders';
        } else {
            alert('Error confirming invoice: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while confirming the invoice.');
    });
});