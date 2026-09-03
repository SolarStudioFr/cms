/**
 * Loads a Module Federation remote container at runtime (no static
 * `remotes` entry, no rebuild needed to add a new plugin) and returns the
 * module it exposes. Standard webpack 5 native "dynamic remote" pattern.
 */
function loadScript(url) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[src="${url}"]`);
        if (existing) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = url;
        script.type = 'text/javascript';
        script.async = true;
        script.onload = resolve;
        script.onerror = () => reject(new Error(`Failed to load remote script: ${url}`));
        document.head.appendChild(script);
    });
}

export default async function loadRemoteModule(scope, remoteEntryUrl, exposedModule) {
    await loadScript(remoteEntryUrl);

    // eslint-disable-next-line no-undef
    await __webpack_init_sharing__('default');

    const container = window[scope];
    if (!container) {
        throw new Error(`Remote container "${scope}" was not found on window after loading ${remoteEntryUrl}`);
    }

    // eslint-disable-next-line no-undef
    await container.init(__webpack_share_scopes__.default);

    const factory = await container.get(exposedModule);

    return factory();
}
