import PortfolioItemList from './PortfolioItemList';
import PortfolioItemForm from './PortfolioItemForm';

/**
 * Contract exposed to the admin host via Module Federation. The host's
 * PluginLoader merges `navItem` into the sidebar and `routes` into the
 * router - both relative to the /adm basename.
 */
export default {
    navItem: {
        label: 'Réalisations',
        path: '/portfolio',
    },
    routes: [
        { path: '/portfolio', element: PortfolioItemList },
        { path: '/portfolio/new', element: PortfolioItemForm },
        { path: '/portfolio/:id/edit', element: PortfolioItemForm },
    ],
};
