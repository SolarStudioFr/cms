import path from 'path';
import { fileURLToPath } from 'url';
import webpack from 'webpack';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
// Output lands under cms/public/build/ so it's covered by the existing
// Apache /build alias - plugin/ itself is HTTP-denied, no vhost.conf change.
const outputPath = path.resolve(__dirname, '../../cms/public/build/plugins/portfolio');

const isProduction = process.env.NODE_ENV === 'production';

export default {
    mode: isProduction ? 'production' : 'development',
    context: __dirname,
    entry: {},
    devtool: isProduction ? false : 'source-map',
    output: {
        path: outputPath,
        publicPath: '/build/plugins/portfolio/',
        uniqueName: 'portfolio_admin',
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
            name: 'portfolio',
            filename: 'remoteEntry.js',
            exposes: {
                './AdminModule': './assets/AdminModule.jsx',
            },
            // The admin host exposes shared components (MediaPicker,
            // RichTextEditor); the builder plugin exposes
            // BuilderCanvas/renderToHtml. Resolved lazily like any MF
            // remote, so no build-order dependency between the three.
            remotes: {
                adm_host: 'adm_host@/build/admHostRemoteEntry.js',
                page_builder: 'page_builder@/build/plugins/page_builder/remoteEntry.js',
            },
            shared: {
                react: { singleton: true, requiredVersion: '^19.2.8' },
                'react-dom': { singleton: true, requiredVersion: '^19.2.8' },
                'react-router-dom': { singleton: true, requiredVersion: '^7.18.3' },
            },
        }),
    ],
};
