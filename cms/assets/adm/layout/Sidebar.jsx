import React from 'react';
import Nav from 'react-bootstrap/Nav';
import { NavLink } from 'react-router-dom';

/**
 * Vertical admin nav. Static items are hardcoded here; plugin-provided
 * items (e.g. from the Page plugin, phase 4) are appended via `extraItems`.
 */
export default function Sidebar({ extraItems = [] }) {
    const items = [
        { label: 'Dashboard', path: '/', icon: null },
        { label: 'Fichiers', path: '/files', icon: null },
        ...extraItems,
    ];

    return (
        <Nav
            className="flex-column bg-dark vh-100 p-3"
            style={{ width: '220px', minWidth: '220px' }}
            variant="pills"
        >
            <span className="text-white-50 text-uppercase small mb-3">Solar CMS</span>
            {items.map((item) => (
                <Nav.Link
                    key={item.path}
                    as={NavLink}
                    to={item.path}
                    end={item.path === '/'}
                    className="text-white"
                >
                    {item.label}
                </Nav.Link>
            ))}
        </Nav>
    );
}
