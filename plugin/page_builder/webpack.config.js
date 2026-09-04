import path from 'path';
import { fileURLToPath } from 'url';
import webpack from 'webpack';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
// Output lands under cms/public/build/ so it's covered by the existing
// Apache /build alias - plugin/ itself is HTTP-denied, no vhost.conf change.
const outputPath = path.resolve(__dirname, '../../cms/public/build/plugins/page_builder');

const isProduction = process.env.NODE_ENV === 'production';

export default {
    mode: isProduction ? 'production' : 'development',
    context: __dirname,
    entry: {},
    devtool: isProduction ? false : 'source-map',
    output: {
        path: outputPath,
        publicPath: '/build/plugins/page_builder/',
        uniqueName: 'page_builder_admin',
    },
    module: {
        rules: [
            {
                test: /\.jsx?$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: [
                            '@babel/preset-env',
                            // Classic runtime (React.createElement, all
                            // *.jsx files already `import React`): avoids
                            // the automatic runtime's react/jsx-(dev-)runtime
                            // module, which doesn't play well with the
                            // Module-Federation-shared React instance here.
                            ['@babel/preset-react', { runtime: 'classic' }],
                        ],
                    },
                },
            },
        ],
    },
    resolve: {
        extensions: ['.js', '.jsx'],
    },
    plugins: [
        new webpack.container.ModuleFederationPlugin({
            // Must match plugin.json's "name" - the host loads this remote
            // by looking up window[manifest.name].
            name: 'page_builder',
            filename: 'remoteEntry.js',
            exposes: {
                './AdminModule': './assets/AdminModule.jsx',
                // Consumed by content plugins (e.g. plugin/page, step 16)
                // as a drop-in replacement for the fallback editor
                // (RichTextEditor, step 09): same value/onChange(JSON
                // string) contract.
                './BuilderCanvas': './assets/BuilderCanvas.jsx',
                // Pure function, no React needed by the caller: converts a
                // BuilderCanvas JSON value into the HTML a content plugin
                // stores/serves publicly - same string a consuming plugin
                // would otherwise get from RichTextEditor directly.
                './renderToHtml': './assets/renderToHtml.js',
            },
            // Needed by builder modules that use the shared media picker
            // (step 03), e.g. the Image/Slider/Download modules (11-13).
            remotes: {
                adm_host: 'adm_host@/build/admHostRemoteEntry.js',
            },
            shared: {
                react: { singleton: true, requiredVersion: '^19.2.8' },
                'react-dom': { singleton: true, requiredVersion: '^19.2.8' },
                'react-router-dom': { singleton: true, requiredVersion: '^7.18.3' },
            },
        }),
    ],
};
