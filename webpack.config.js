const path = require('path');

const ProvidePlugin = require('webpack/lib/ProvidePlugin');
const CommonsChunkPlugin = require('webpack/lib/optimize/CommonsChunkPlugin');
const UglifyJsPlugin = require('webpack/lib/optimize/UglifyJsPlugin');
const ExtractTextPlugin = require('extract-text-webpack-plugin');

const isProd = process.env.NODE_ENV === 'production';

let plugins = [
    new ProvidePlugin({
        $: 'jquery',
        jQuery: 'jquery',
        'window.jQuery': 'jquery',
        Popper: ['popper.js', 'default'],
    }),
    new CommonsChunkPlugin({
        name: 'vendors',
        chunks: ['main', 'post/default'],
        minChunks: Infinity,
    }),
    new ExtractTextPlugin({
        filename: 'css/[name].css',
        allChunks: true,
    }),
];

if (isProd) {
    plugins = plugins.concat([
        new UglifyJsPlugin(),
    ]);
}

module.exports = {
    entry: {
        'main': [
            './assets/js/main.js',
            './assets/scss/main.scss',
        ],
        'post/default': [
            './assets/js/post/default.js',
            './assets/scss/post/default.scss',
        ],
        'vendors': [
            './assets/js/vendors.js',
            './assets/scss/vendors.scss',
        ],
    },
    output: {
        path: path.resolve(__dirname, './web'),
        filename: 'js/[name].js',
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {presets: ['es2015']},
                },
            },
            {
                test: /\.scss$/,
                use: ExtractTextPlugin.extract({
                    fallback: 'style-loader',
                    use: [
                        'css-loader',
                        {
                            loader: 'postcss-loader',
                            options: {
                                plugins: () => {
                                    return [
                                        require('precss'),  // bootstrap4 requires this
                                        require('autoprefixer')({ browsers: ['last 2 versions'] }),
                                    ];
                                },
                            },
                        },
                        {
                            loader: 'sass-loader',
                        },
                    ],
                }),
            },
            {
                // for font-awesome
                test: /\.(ttf|otf|eot|svg|woff2?)$/,
                use: {
                    loader: 'file-loader',
                    options: {
                        name: '[name].[ext]',
                        outputPath: 'fonts/',
                        publicPath: '../',
                    },
                },
            },
        ],
    },
    devtool: isProd ? 'source-map' : 'inline-source-map',
    plugins: plugins,
};
