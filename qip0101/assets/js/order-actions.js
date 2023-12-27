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
    }
});