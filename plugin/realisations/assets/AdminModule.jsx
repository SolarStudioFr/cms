import RealisationList from './RealisationList';
import RealisationForm from './RealisationForm';

/**
 * Contract exposed to the admin host via Module Federation. The host's
 * PluginLoader merges `navItem` into the sidebar and `routes` into the
 * router - both relative to the /adm basename.
 */
export default {
    navItem: {
        label: 'Réalisations',
        path: '/realisations',
    },
    routes: [
        { path: '/realisations', element: RealisationList },
        { path: '/realisations/new', element: RealisationForm },
        { path: '/realisations/:id/edit', element: RealisationForm },
    ],
};
