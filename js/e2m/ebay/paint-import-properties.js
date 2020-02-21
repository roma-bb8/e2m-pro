function paintImportProperties() {
    var eBayFieldList = $('field-attribute-list-e');
    var magentoFieldList = $('field-attribute-list-m');
    if (eBayFieldList === null || magentoFieldList === null) {
        return;
    }

    eBayFieldList.innerHTML = '';
    magentoFieldList.innerHTML = '';

    for (var [attributeCode, eBayCode] of Object.entries(e2m.attributes)) {

        var attributeCodeSpan = document.createElement('span');
        attributeCodeSpan.innerHTML = ">&nbsp;&nbsp;" + e2m.magentoAttributes[attributeCode];
        attributeCodeSpan.append(document.createElement('br'));
        magentoFieldList.append(attributeCodeSpan);

        var eBayCodeSpan = document.createElement('span');
        eBayCodeSpan.innerHTML = e2m.eBayFields[eBayCode];
        eBayCodeSpan.append(document.createElement('br'));
        eBayFieldList.append(eBayCodeSpan);
    }
}
