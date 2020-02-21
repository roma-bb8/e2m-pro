function importInventoryHandler(data) {
    if (data.process === 100) {
        setTimeout(function () {
            window.location.reload();
        }, 3000);
    }

    $('import-inventory-progress').innerHTML = data.process;
}
