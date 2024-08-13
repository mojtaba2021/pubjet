const path = require('path');
const webpack = require('webpack');
const ReactRefreshWebpackPlugin = require(
    '@pmmmwh/react-refresh-webpack-plugin');

/**
 * Configure Webpack Dev Server.
 *
 * @return {Object}
 */
const configureDevServer = () => {
    return {
        host        : "localhost",
        port        : 8090,
        hot         : true,
        compress    : true,
        liveReload  : true,
        allowedHosts: ['all'],
        headers     : {
            'Access-Control-Allow-Origin' : '*',
            'Access-Control-Allow-Headers': 'Origin, X-Requested-With, Content-Type, Accept'
        },
    };
};

/**
 * Export the config.
 *
 * @type {Object}
 */
module.exports = {
    devServer: configureDevServer(),
    devtool  : 'eval-cheap-module-source-map',
    entry    : {
        admin: './src/admin/main.js',
    },
    mode     : 'development',
    module   : {
        rules: [
            {
                test: /\.css$/,
                use : ['style-loader', 'css-loader'],
            },
            {
                test: /\.s[c|a]ss$/,
                use : [
                    {
                        // Adds CSS to the DOM by injecting a `<style>` tag
                        loader: 'style-loader'
                    },
                    {
                        // Interprets `@import` and `url()` like `import/require()` and will resolve them
                        loader: 'css-loader'
                    },
                    {
                        // Loader for webpack to process CSS with PostCSS
                        loader: 'postcss-loader',
                    },
                    {
                        // Loads a SASS/SCSS file and compiles it to CSS
                        loader: 'sass-loader'
                    }
                ],
            },
            {
                test   : /\.js$/,
                exclude: /node_modules/,
                use    : 'babel-loader',
            },
        ]
    },
    output   : {
        filename  : '[name].js',
        path      : path.join(__dirname, '/dist'),
        publicPath: 'http://localhost:8090/',
    },
    plugins  : [
        new webpack.HotModuleReplacementPlugin(),
        new ReactRefreshWebpackPlugin(),
    ],
};