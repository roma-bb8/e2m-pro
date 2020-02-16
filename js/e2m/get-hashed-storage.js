function getHashedStorage(id) {

    var hashedStorageKey = e2m.prefix + '_' + md5(id).substr(0, 10);
    if (typeof e2m.localStorage[hashedStorageKey] === 'undefined') {
        return '';
    }

    return e2m.localStorage[hashedStorageKey];
}
