function setHashedStorage(id) {
    e2m.localStorage[e2m.prefix + '_' + md5(id).substr(0, 10)] = 1;
    localStorage.setItem(e2m.prefix, JSON.stringify(e2m.localStorage));
}
