import PageList from './PageList';
import PageForm from './PageForm';

/**
 * Contract exposed to the admin host via Module Federation. The host's
 * PluginLoader merges `navItem` into the sidebar and `routes` into the
 * router - both relative to the /adm basename.
 */
export default {
    navItem: {
        label: 'Pages',
        path: '/pages',
    },
    routes: [
        { path: '/pages', element: PageList },
        { path: '/pages/new', element: PageForm },
        { path: '/pages/:id/edit', element: PageForm },
    ],
};
