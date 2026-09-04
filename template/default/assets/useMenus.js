import { useEffect, useState } from 'react';
import client from './api/client';

/**
 * Fetches every admin-defined menu currently attached to a hook (step 33:
 * GET /api/menus, already filtered server-side to hookName !== null - see
 * App\State\PublicMenuCollectionProvider). Fetched once and shared by every
 * <MenuHook> on the page rather than one request per hook.
 */
export default function useMenus() {
    const [menus, setMenus] = useState([]);

    useEffect(() => {
        client.get('/menus').then(({ data }) => setMenus(data));
    }, []);

    return menus;
}
