import { useEffect, useState } from 'react';
import client from '../api/client';
import loadRemoteModule from './loadRemoteModule';

/**
 * Fetches the active plugin manifests from the backend (PluginRegistry) and
 * dynamically loads each one's Module Federation remote - no rebuild of the
 * admin host is needed to pick up a newly installed/activated plugin.
 *
 * `enabled` gates the fetch: /api/admin/plugins requires ROLE_SUPER_ADMIN,
 * so this must only run once the user is actually authenticated (it re-runs
 * when `enabled` flips from false to true, i.e. right after login).
 */
export default function usePlugins(enabled) {
    const [plugins, setPlugins] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!enabled) {
            return undefined;
        }

        let cancelled = false;
        setLoading(true);

        client
            .get('/admin/plugins')
            .then(async ({ data: manifests }) => {
                const loaded = await Promise.all(
                    manifests.map(async (manifest) => {
                        try {
                            const remoteModule = await loadRemoteModule(
                                manifest.name,
                                manifest.remoteEntry,
                                manifest.exposedModule,
                            );
                            return remoteModule.default ?? remoteModule;
                        } catch (error) {
                            // eslint-disable-next-line no-console
                            console.error(`Failed to load plugin "${manifest.name}"`, error);
                            return null;
                        }
                    }),
                );

                if (!cancelled) {
                    setPlugins(loaded.filter(Boolean));
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [enabled]);

    return { plugins, loading };
}
