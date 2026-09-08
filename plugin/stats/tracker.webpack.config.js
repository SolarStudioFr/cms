import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
// Same /build/plugins/stats/ output directory as the admin remote
// (webpack.config.js) - the two builds are independent (no Module
// Federation, no shared deps here) but share one output folder/URL prefix.
const outputPath = path.resolve(__dirname, '../../cms/public/build/plugins/stats');

const isProduction = process.env.NODE_ENV === 'production';

export default {
    mode: isProduction ? 'production' : 'development',
    context: __dirname,
    entry: './assets/tracker.js',
    devtool: isProduction ? false : 'source-map',
    output: {
        path: outputPath,
        publicPath: '/build/plugins/stats/',
        filename: 'tracker.js',
    },
};
