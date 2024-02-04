function attachOrderActionEventHandlers() {
    document.querySelectorAll('.sell-item-btn').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.itemId; // Updated to use data-item-id instead of data-order-id
            const input = document.querySelector('.sell-quantity-input[data-item-id="' + itemId + '"]');
            const quantityToSell = input.value;
            if (quantityToSell) {
                sellItem(itemId, quantityToSell);
            }
        });
    });

    document.querySelectorAll('.return-item-btn').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.itemId; // Updated to use data-item-id instead of data-order-id
            const input = document.querySelector('.return-quantity-input[data-item-id="' + itemId + '"]');
            const quantityToReturn = input.value;
            if (quantityToReturn) {
                returnItem(itemId, quantityToReturn);
            }
        });
    });
}
// Function to be called when the document is ready
function onDocumentReady() {
    attachOrderActionEventHandlers();

    // Other initialization code can go here if needed
}

// Attach handlers when the DOM is fully loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onDocumentReady);
} else {
    onDocumentReady(); // DOMContentLoaded has already fired
}

// Attach event handler for finalize deal button
const finalizeDealBtn = document.getElementById('finalize-deal-btn');
if (finalizeDealBtn) {
    finalizeDealBtn.addEventListener('click', finalizeDeal);
}
// Rest of your existing functions (sellItem, returnItem, finalizeDeal, etc.)

function sellItem(itemId, quantityToSell) {
    fetch('/index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'sell', itemId: itemId, quantityToSell: quantityToSell })    })
    .then(response => response.json()) 
    .then(data => {
        console.log(data); // Check the response data
        if (data.success) {
            // Update the order row to reflect the new stock and sold quantities
            const row = document.querySelector('tr[data-item-id="' + itemId + '"]');
            row.querySelector('.sold').textContent = data.updatedSold;
            row.querySelector('.stock').textContent = data.updatedStock;
            // Reset the input field
            document.querySelector('.sell-quantity-input[data-item-id="' + itemId + '"]').value = '';
        } else {
            alert('Error selling items: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while selling the items.');
    });
}

function returnItem(itemId, quantityToReturn) {
    fetch('/index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'return', itemId: itemId, quantityToReturn: quantityToReturn })
    })
    .then(response => response.json()) 
    .then(data => {
        console.log(data); // Check the response data
        if (data.success) {
            // Correct field updates for return operation
            const row = document.querySelector('tr[data-item-id="' + itemId + '"]');
            row.querySelector('.returned').textContent = data.updatedReturn; // Update the returned quantity
            row.querySelector('.stock').textContent = data.updatedStock; // Ensure this reflects the correct new stock
            document.querySelector('.return-quantity-input[data-item-id="' + itemId + '"]').value = '';
        } else {
            alert('Error returning items: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while returning the items.');
    });
}
//modified
function finalizeDeal() {
    const orderRows = Array.from(document.querySelectorAll('#orders-table tbody tr'));
    const hasSoldOrReturnedItems = orderRows.some(row => 
        parseInt(row.querySelector('.sold').textContent, 10) > 0 || 
        parseInt(row.querySelector('.returned').textContent, 10) > 0
    );

    if (!hasSoldOrReturnedItems) {
        alert('You have zero Sold and Returned items. Cannot finalize deal.');
        return; // Stop the function from proceeding
    }

    const itemIds = orderRows.map(row => row.dataset.itemId);
    fetch('/index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'finalize_deal', itemIds: itemIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Deal has been finalized successfully!');
            // Create and display Invoice button
            const invoiceBtn = document.createElement("button");
            invoiceBtn.id = "invoice-btn";
            invoiceBtn.textContent = "Generate Invoice";
            invoiceBtn.onclick = function() {
                window.location.href = 'v/invoice.php';
            };
            document.body.appendChild(invoiceBtn);
        } else {
            alert('Error finalizing deal: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while finalizing the deal.');
    });
}
//original
// function finalizeDeal() {
//     const itemIds = Array.from(document.querySelectorAll('#orders-table tr')).map(row => row.dataset.itemId);
//     fetch('/index.php', {
//         method: 'POST',
//         headers: { 'Content-Type': 'application/json' },
//         body: JSON.stringify({ action: 'finalize_deal', itemIds: itemIds })
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.success) {
//             alert('Deal has been finalized successfully!');
//             // Create and display Invoice button
//             const invoiceBtn = document.createElement("button");
//             invoiceBtn.id = "invoice-btn";
//             invoiceBtn.textContent = "Generate Invoice";
//             invoiceBtn.onclick = function() {
//                 window.location.href = 'v/invoice.php';
//             };
//             document.body.appendChild(invoiceBtn);
//         } else {
//             alert('Error finalizing deal: ' + data.message);
//         }
//     })
//     .catch(error => {
//         console.error('Error:', error);
//         alert('An error occurred while finalizing the deal.');
//     });
// }

function handleOrderUpdate(response, itemId, newQuantity, unitPrice) {
    if (response.success) {
        const quantitySpan = document.querySelector(".quantity[data-id='" + itemId + "']");
        const totalSpan = document.querySelector(".total-price[data-id='" + itemId + "']");
        quantitySpan.textContent = newQuantity;
        totalSpan.textContent = (newQuantity * unitPrice).toFixed(2); // Update the total price
    } else {
        alert(response.message || 'An error occurred while updating the order.');
    }
}

function sendOrderUpdate(itemId, newQuantity, unitPrice) {
    fetch('/index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ action: newQuantity > 0 ? 'update_order' : 'delete_item', itemId: itemId, quantity: newQuantity })
    })
    .then(response => response.json())
    .then(data => handleOrderUpdate(data, itemId, newQuantity, unitPrice))
    .catch(error => console.error('Error:', error));
}

document.addEventListener('click', function(event) {
    if (event.target.matches('.increase-quantity, .decrease-quantity')) {
        const itemId = event.target.dataset.id;
        const quantitySpan = document.querySelector(".quantity[data-id='" + itemId + "']");
        const unitPrice = parseFloat(document.querySelector(".unit-price[data-id='" + itemId + "']").textContent);
        let newQuantity = parseInt(quantitySpan.textContent);

        newQuantity += event.target.matches('.increase-quantity') ? 1 : -1;
        
        if (newQuantity >= 0) { // Prevent negative quantities
            sendOrderUpdate(itemId, newQuantity, unitPrice);
        }
    } else if (event.target.matches('.delete-item')) {
        const itemId = event.target.dataset.id;
        event.target.closest('tr').remove(); // Remove the row from the DOM
        sendOrderUpdate(itemId, 0, 0); // Send delete request to the server
    }else if (event.target.id === 'confirm-order-btn') {
    fetch('/index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ action: 'confirm_order' })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.success ? 'Order confirmed!' : 'Error confirming order: ' + data.message);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while confirming the order.');
    })
    .finally(() => {
        // Clear the orders table regardless of the result.
        document.querySelector('#orders-table tbody').innerHTML = '';
    });
    }

});