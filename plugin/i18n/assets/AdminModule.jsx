import LangManager from './LangManager';
import TranslationManager from './TranslationManager';

/**
 * Contract exposed to the admin host via Module Federation. The host's
 * PluginLoader merges `navItem` into the sidebar and `routes` into the
 * router - both relative to the /adm basename. Only one nav item is
 * declared (Langues); Traductions is reached from there rather than
 * doubling up the sidebar for a two-page plugin.
 */
export default {
    navItem: {
        label: 'Langues',
        path: '/langs',
    },
    routes: [
        { path: '/langs', element: LangManager },
        { path: '/translations', element: TranslationManager },
    ],
};
