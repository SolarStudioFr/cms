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
    .cleanupOutputBeforeBuild()

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
    // singleton so plugin remotes reuse the host's instance.
    .addPlugin(new webpack.container.ModuleFederationPlugin({
        name: 'adm_host',
        shared: {
            react: { singleton: true, requiredVersion: '^19.2.8' },
            'react-dom': { singleton: true, requiredVersion: '^19.2.8' },
            'react-router-dom': { singleton: true, requiredVersion: '^7.18.3' },
        },
    }))
;

export default await Encore.getWebpackConfig();
