import Dashboard from './Dashboard';

/**
 * Contract exposed to the admin host via Module Federation. The host's
 * PluginLoader merges `navItem` into the sidebar and `routes` into the
 * router - both relative to the /adm basename.
 */
export default {
    navItem: {
        label: 'Statistiques',
        path: '/stats',
    },
    routes: [{ path: '/stats', element: Dashboard }],
};
