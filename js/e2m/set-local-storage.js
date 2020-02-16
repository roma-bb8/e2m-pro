function setLocalStorage(key, value) {
    e2m.localStorage[key] = value;
    return localStorage.setItem('m2e_e2m_data', JSON.stringify(e2m.localStorage));
}
