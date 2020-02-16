function deleteHashedStorage(id) {
    removeLocalStorage(e2m.prefix + md5(id).substr(0, 10));
    removeLocalStorage(id);
}
