import { useEffect, useState } from 'react';
import client from '../api/client';

/**
 * Fetches the saved admin sidebar order (AdminMenuConfig, step 32 add-on).
 * Same auth-gating shape as usePlugins.js: /api/admin/admin-menu-config
 * requires ROLE_SUPER_ADMIN, so this must only run once authenticated.
 *
 * @param {boolean} enabled
 * @returns {{items: Array<{type: 'item', key: string} | {type: 'separator'}>, loading: boolean, save: (items: Array) => Promise<void>}}
 */
export default function useAdminMenuOrder(enabled) {
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!enabled) {
            return;
        }
        setLoading(true);
        client
            .get('/admin/admin-menu-config')
            .then(({ data }) => setItems(data.items))
            .finally(() => setLoading(false));
    }, [enabled]);

    const save = async (nextItems) => {
        const { data } = await client.patch('/admin/admin-menu-config', { items: nextItems });
        setItems(data.items);
    };

    return { items, loading, save };
}
