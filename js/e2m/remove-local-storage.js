function removeLocalStorage(key) {
    if (typeof e2m.localStorage[key] === 'undefined') {
        return false;
    }

    delete e2m.localStorage[key];
    localStorage.setItem(e2m.prefix, JSON.stringify(e2m.localStorage));
    return true;
}
