'use strict';

const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
// const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const {DeleteSourceMapsPlugin} = require('webpack-delete-sourcemaps-plugin');

const minimize = process.env.npm_config_minimize === 'true';
const frontFilename = minimize ? 'theme.min' : 'theme';
const adminFilename = minimize ? 'admin.min' : 'admin';

const entries = {
    [frontFilename]: './src/theme/main.js',
    [adminFilename]: './src/admin/main.js',
};

/**
 * @type {Object}
 */
module.exports = {
    entry: entries,
    mode: 'production',
    optimization: {
        minimize: true,
        moduleIds: 'deterministic',
        minimizer: [
            // new OptimizeCSSAssetsPlugin({
            //   cssProcessorOptions: {
            //     discardComments: true,
            //     removeAll      : true,
            //     map            : {
            //       inline    : false,
            //       annotation: true,
            //     },
            //     safe           : true,
            //   },
            // }),
            new TerserPlugin({
                parallel: true,
                terserOptions: {
                    format: {
                        comments: false,
                    },
                },
                extractComments: false,
            }),
        ],
        // splitChunks : {
        //   cacheGroups: {
        //     commons: {
        //       test  : /[\\/]node_modules[\\/]/,
        //       name  : 'vendor',
        //       chunks: 'initial',
        //     },
        //   },
        // },
    },
    module: {
        rules: [
            {
                exclude: /node_modules/,
                test: /\.js$/,
                use: 'babel-loader',
            },
            {
                test: /\.s[c|a]ss$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader,
                    },
                    {
                        loader: 'css-loader',
                        options: {
                            sourceMap: false,
                        },
                    },
                    {
                        loader: 'postcss-loader',
                    },
                    {
                        loader: 'sass-loader',
                        options: {
                            sassOptions: {
                                sourceMap: false,
                                minimize: minimize,
                                outputStyle: 'expanded',
                            },
                        },
                    },
                ],
            },
            {
                test: /\.css$/i,
                use: [
                    'style-loader',
                    {
                        loader: 'css-loader',
                        options: {
                            sourceMap: false,
                        },
                    },
                ],
            },
            {
                test: /\.(woff|woff2|eot|ttf|svg)$/,
                loader: 'url-loader',
                options: {
                    limit: 1000000,
                    name: './fonts/[name].[ext]?[hash]',
                },
            },
            {
                test: /\.(png|jpe?g|gif)$/,
                use: {
                    loader: 'url-loader',
                    options: {
                        name: '[name].[ext]',
                        limit: 1000000,
                        publicPath: process.env.PUBLIC_PATH + '/assets/img',
                    },
                },
            },
        ],
    },
    output: {
        publicPath: process.env.PUBLIC_PATH,
        path: path.join(__dirname, '/assets'),
        filename: 'js/[name].[contenthash].bundle.js',
        chunkFilename: 'js/[name].[contenthash].bundle.js',
        clean: {
            keep: /images|img|fonts|libs/, // Keep these assets under 'ignored/dir'.
        },
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: './css/[name].[contenthash].min.css',
            chunkFilename: './css/[name].[contenthash].min.css',
        }),
        new DeleteSourceMapsPlugin(),
        //new BundleAnalyzerPlugin()
    ],
};