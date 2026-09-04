/**
 * Static (non-plugin) admin nav entries, keyed for AdminMenuConfig
 * (step 32 add-on: admin sidebar ordering) - both Sidebar.jsx (default
 * order/fallback for unconfigured keys) and AdminMenuSettings.jsx (the
 * picker of "available" items) read from this single list so they can
 * never drift apart.
 */
export default [
    { key: 'dashboard', label: 'Dashboard', path: '/' },
    { key: 'files', label: 'Fichiers', path: '/files' },
    { key: 'plugins', label: 'Plugins', path: '/plugins' },
    { key: 'menus', label: 'Menus', path: '/menus' },
    { key: 'users', label: 'Utilisateurs', path: '/users' },
    { key: 'settings', label: 'Configuration', path: '/settings' },
    { key: 'admin-menu', label: 'Menu admin', path: '/admin-menu' },
];
