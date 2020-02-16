function deleteHashedStorage(id) {
    removeLocalStorage(e2m.prefix + '_' + md5(id).substr(0, 10));
    removeLocalStorage(id);
}
