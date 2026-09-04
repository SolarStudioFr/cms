import HomepageForm from './HomepageForm';

/**
 * Contract exposed to the admin host via Module Federation. Unlike the
 * other content plugins, the homepage is a singleton: a single nav entry
 * opens directly the edit form, no list/new routes.
 */
export default {
    navItem: {
        label: 'Accueil',
        path: '/homepage',
    },
    routes: [{ path: '/homepage', element: HomepageForm }],
};
