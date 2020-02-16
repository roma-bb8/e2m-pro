function downloadInventoryHandler(data) {

    $('download-inventory-progress').innerHTML = data.process;

    $('download-inventory-total-items').innerHTML = data.total;
    $('download-inventory-variation-items').innerHTML = data.variation;
    $('download-inventory-simple-items').innerHTML = data.simple;
}
