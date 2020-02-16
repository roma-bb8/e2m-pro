function utf8Encode(data) {
    data = data.replace(/\r\n/g, "\n");

    var result = '';
    for (var i = 0; i < data.length; i++) {
        var char = data.charCodeAt(i);

        if (char < 128) {
            result += String.fromCharCode(char);
            continue;
        }

        if ((char > 127) && (char < 2048)) {
            result += String.fromCharCode((char >> 6) | 192);
            result += String.fromCharCode((char & 63) | 128);
            continue;
        }

        result += String.fromCharCode((char >> 12) | 224);
        result += String.fromCharCode(((char >> 6) & 63) | 128);
        result += String.fromCharCode((char & 63) | 128);
    }

    return result;
}
