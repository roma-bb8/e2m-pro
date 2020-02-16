function getLocalStorage(key) {
    if (typeof e2m.localStorage[key] === 'undefined') {
        return null;
    }

    return e2m.localStorage[key];
}
