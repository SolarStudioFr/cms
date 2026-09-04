import React from 'react';
import Nav from 'react-bootstrap/Nav';
import { NavLink } from 'react-router-dom';
import staticNavItems from './staticNavItems';
import resolveNavOrder from './resolveNavOrder';
import useAdminMenuOrder from './useAdminMenuOrder';

/**
 * Vertical admin nav. Static items are declared in staticNavItems.js;
 * plugin-provided items are appended via `pluginItems` (each carrying a
 * `key: 'plugin:<name>'`, see usePlugins.js). Display order (and optional
 * separators between entries) comes from AdminMenuConfig (step 32 add-on,
 * useAdminMenuOrder) - unconfigured/unknown items fall back to the default
 * order, see resolveNavOrder.js, so the sidebar always works even before
 * this is ever set up in /admin-menu.
 */
export default function Sidebar({ pluginItems = [] }) {
    const { items: savedOrder } = useAdminMenuOrder(true);
    const knownItems = [...staticNavItems, ...pluginItems];
    const resolved = resolveNavOrder(knownItems, savedOrder);

    return (
        <Nav
            className="flex-column bg-dark vh-100 p-3"
            style={{ width: '220px', minWidth: '220px' }}
            variant="pills"
        >
            <span className="text-white-50 text-uppercase small mb-3">Solar CMS</span>
            {resolved.map((entry) =>
                'separator' === entry.kind ? (
                    <hr key={entry.id} className="text-white-50 my-2" />
                ) : (
                    <Nav.Link
                        key={entry.key}
                        as={NavLink}
                        to={entry.path}
                        end={'/' === entry.path}
                        className="text-white"
                    >
                        {entry.label}
                    </Nav.Link>
                ),
            )}
        </Nav>
    );
}
