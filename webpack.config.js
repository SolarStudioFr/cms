import Encore from '@symfony/webpack-encore';
import webpack from 'webpack';

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('cms/public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or subdirectory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('default', './template/default/assets/index.jsx')
    .addEntry('adm', './cms/assets/adm/index.jsx')

    // Static assets shared by both entries (e.g. the file-manager fallback
    // image used when a File entity's underlying disk file is missing),
    // copied as-is rather than imported so they get a stable /build/images/
    // URL independent of either entry's JS bundle.
    .copyFiles({
        from: './cms/assets/images',
        to: 'images/[path][name].[ext]',
    })

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    // Plugin remotes (plugin/*/webpack.config.js) emit into
    // cms/public/build/plugins/**, a raw (non-Encore) webpack build that
    // runs as a separate `npm run build:plugins` step, not part of this
    // config's own output. Without `keep`, this defaults to wiping the
    // *entire* output.path (webpack 5's native output.clean) on every host
    // build, silently deleting every already-built plugin remote - the
    // admin then fails to load any plugin ("Remote container ... was not
    // found") until `build:plugins` is re-run. `keep` (a RegExp tested
    // against the path relative to output.path) preserves that directory.
    .cleanupOutputBeforeBuild((options) => {
        options.keep = /^plugins\//;
    })

    // Displays build status system notifications to the user
    // .enableBuildNotifications()

    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // Configure JS and CSS minimizers
    .configureJsMinimizerPlugin((options, MinimizerPlugin) => {
        options.minify = MinimizerPlugin.esbuildMinify
    })
    .configureCssMinimizerPlugin((options, MinimizerPlugin) => {
        options.minify = MinimizerPlugin.lightningCssMinify;
    })

    // configure Babel
    .configureBabel((config) => {
        config.plugins.push(['polyfill-corejs3', { method: 'usage-global', version: '3.49' }]);
    })

    // enables Sass/SCSS support
    .enableSassLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    // Classic runtime (React.createElement): the automatic runtime's
    // react/jsx-(dev-)runtime module doesn't play well with the
    // Module-Federation-shared React instance (adm entry is a MF host).
    // Every .jsx file already `import React`, so this is a safe switch.
    .enableReactPreset((config) => {
        config.runtime = 'classic';
    })

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    .enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()

    // Module Federation host: no static remotes (they're discovered and
    // loaded dynamically at runtime, see cms/assets/adm/plugins/), this
    // just exposes the __webpack_init_sharing__/__webpack_share_scopes__
    // globals that dynamic remote loading needs, with React shared as a
    // singleton so plugin remotes reuse the host's instance. It also
    // exposes a small set of shared admin components (MediaPicker, step 03;
    // RichTextEditor, step 09) so plugins can consume them instead of
    // duplicating them - a plugin remote declares `remotes: { adm_host: ... }`
    // pointing at this container's filename (see plugin/page/webpack.config.js).
    .addPlugin(new webpack.container.ModuleFederationPlugin({
        name: 'adm_host',
        filename: 'admHostRemoteEntry.js',
        exposes: {
            './MediaPicker': './cms/assets/adm/components/MediaPicker.jsx',
            './RichTextEditor': './cms/assets/adm/components/RichTextEditor.jsx',
        },
        shared: {
            react: { singleton: true, requiredVersion: '^19.2.8' },
            'react-dom': { singleton: true, requiredVersion: '^19.2.8' },
            'react-router-dom': { singleton: true, requiredVersion: '^7.18.3' },
        },
    }))
;

export default await Encore.getWebpackConfig();
