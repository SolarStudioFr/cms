import LangManager from './LangManager';

/**
 * Contract exposed to the admin host via Module Federation. The host's
 * PluginLoader merges `navItem` into the sidebar and `routes` into the
 * router - both relative to the /adm basename.
 */
export default {
    navItem: {
        label: 'Langues',
        path: '/langs',
    },
    routes: [{ path: '/langs', element: LangManager }],
};
