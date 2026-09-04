import ActualiteList from './ActualiteList';
import ActualiteForm from './ActualiteForm';

/**
 * Contract exposed to the admin host via Module Federation. The host's
 * PluginLoader merges `navItem` into the sidebar and `routes` into the
 * router - both relative to the /adm basename.
 */
export default {
    navItem: {
        label: 'Actualités',
        path: '/actualites',
    },
    routes: [
        { path: '/actualites', element: ActualiteList },
        { path: '/actualites/new', element: ActualiteForm },
        { path: '/actualites/:id/edit', element: ActualiteForm },
    ],
};
