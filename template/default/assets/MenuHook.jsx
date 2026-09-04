import React from 'react';
import { Link } from 'react-router-dom';
import Nav from 'react-bootstrap/Nav';

/**
 * Renders, at one template hook, whichever admin-defined Menu is attached
 * to it (step 33) - or nothing at all if no menu is attached, or the
 * attached menu has no items yet ("no plugin/feature is ever blocking").
 * A link whose URL starts with "/" is routed client-side (Link); anything
 * else (an absolute URL) is a plain anchor, honoring the item's `target`.
 *
 * @param {string} name hook name declared in template/default/theme.json
 * @param {Array} menus every hook-attached menu, from useMenus()
 * @param {string} [className]
 */
export default function MenuHook({ name, menus, className = '' }) {
    const menu = menus.find((candidate) => candidate.hookName === name);

    if (!menu || 0 === menu.items.length) {
        return null;
    }

    return (
        <Nav className={className}>
            {menu.items.map((item) =>
                'separator' === item.type ? (
                    <span key={item.id} className="vr mx-2 my-1" />
                ) : item.url?.startsWith('/') ? (
                    <Nav.Link key={item.id} as={Link} to={item.url} target={item.target}>
                        {item.label}
                    </Nav.Link>
                ) : (
                    <Nav.Link key={item.id} href={item.url} target={item.target}>
                        {item.label}
                    </Nav.Link>
                ),
            )}
        </Nav>
    );
}
