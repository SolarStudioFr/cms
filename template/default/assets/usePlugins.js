import { useEffect, useState } from 'react';
import client from './api/client';

/**
 * Fetches the currently enabled plugin names (GET /api/plugins/enabled) so
 * the public theme can actually hide a disabled plugin's nav links/routes/
 * forms - unlike the admin side, this theme hardcodes its plugin-backed
 * routes/components (Portfolio, News, Newsletter) rather than discovering
 * them dynamically, so it needs this to know which of them are currently
 * live. Starts as an empty set (nothing shown) until the fetch resolves, to
 * avoid a flash of plugin UI that immediately disappears if disabled.
 */
export default function usePlugins() {
    const [enabled, setEnabled] = useState(new Set());
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        client
            .get('/plugins/enabled')
            .then(({ data }) => setEnabled(new Set(data)))
            .finally(() => setLoading(false));
    }, []);

    return { enabled, loading };
}
