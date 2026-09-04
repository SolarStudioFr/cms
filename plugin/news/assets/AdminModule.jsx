import NewsArticleList from './NewsArticleList';
import NewsArticleForm from './NewsArticleForm';

/**
 * Contract exposed to the admin host via Module Federation. The host's
 * PluginLoader merges `navItem` into the sidebar and `routes` into the
 * router - both relative to the /adm basename.
 */
export default {
    navItem: {
        label: 'Actualités',
        path: '/news',
    },
    routes: [
        { path: '/news', element: NewsArticleList },
        { path: '/news/new', element: NewsArticleForm },
        { path: '/news/:id/edit', element: NewsArticleForm },
    ],
};
