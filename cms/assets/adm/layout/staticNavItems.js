/**
 * Static (non-plugin) admin nav entries, keyed for AdminMenuConfig
 * (step 32 add-on: admin sidebar ordering) - both Sidebar.jsx (default
 * order/fallback for unconfigured keys) and AdminMenuSettings.jsx (the
 * picker of "available" items) read from this single list so they can
 * never drift apart. `label` is an "admin" domain translation key (step
 * 54) rather than raw text - render it through t(). Plugin-provided items
 * (see App.jsx) carry a raw French label instead, with no matching catalog
 * entry, so passing them through the same t() call is a harmless no-op
 * (useTranslator() falls back to the key itself when it finds no match).
 */
export default [
    { key: 'dashboard', label: 'nav.dashboard', path: '/' },
    { key: 'pages', label: 'nav.pages', path: '/pages' },
    { key: 'files', label: 'nav.files', path: '/files' },
    { key: 'plugins', label: 'nav.plugins', path: '/plugins' },
    { key: 'menus', label: 'nav.menus', path: '/menus' },
    { key: 'users', label: 'nav.users', path: '/users' },
    { key: 'settings', label: 'nav.settings', path: '/settings' },
    { key: 'admin-menu', label: 'nav.adminMenu', path: '/admin-menu' },
];
