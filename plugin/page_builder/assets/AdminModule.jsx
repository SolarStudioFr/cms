/**
 * Contract exposed to the admin host via Module Federation. Unlike the
 * page/i18n plugins, the builder has no standalone admin section of its
 * own (no navItem/routes) - it's a library other plugins embed
 * (BuilderCanvas, renderToHtml, both exposed separately, see
 * webpack.config.js) rather than something an admin browses directly.
 * Still required so PluginRegistry/usePlugins.js can discover and load it.
 */
export default {
    navItem: null,
    routes: [],
};
