/* jshint esversion: 6 */
/* globals  require, module, process, __dirname */

// Fix issue with monorepo and some plugins
//https://github.com/jantimon/html-webpack-plugin/issues/1451#issuecomment-712581727
const _require = id => require(require.resolve(id, { paths: [require.main.path] }));

// Libs
const webpack = _require('webpack');
const { merge } = require('webpack-merge');
const path = require('path');
const fs = require('fs');
const touch = require('touch');

// Plugins
const { WebpackManifestPlugin } = _require('webpack-manifest-plugin');
const ParentModule = require('parent-module');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserWebpackPlugin = require('terser-webpack-plugin');
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const {CleanWebpackPlugin} = require('clean-webpack-plugin');
const Dotenv = require('dotenv-webpack');
const RemovePlugin = require('remove-files-webpack-plugin');
const ExtraWatchWebpackPlugin = require('extra-watch-webpack-plugin');

class WebpackForceRebuildOnEmitPlugin {
    apply(compiler) {
        compiler.hooks.emit.tapAsync('WebpackForceRebuildOnEmitPlugin', (compilation, callback) => {
            const outputPath = compilation.outputOptions.path;
            const firstAssetName = compilation.getAssets()[0].name;
            const assetToTouch = path.resolve(outputPath, firstAssetName);
            touch(assetToTouch);
            callback();
        });
    }
}

/**
 * CraftWebpackConfig class
 */
class CraftWebpackConfig {
    constructor(options = {}) {
        this.types = [
            'asset',
            'base',
            'lib',
            'vue',
        ];

        this.basePath = path.dirname(ParentModule());

        // env
        this.rootPath = path.resolve('./');
        let assetEnvPath = path.join(this.basePath, './.env');
        let rootEnvPath = path.resolve(this.rootPath, './.env');

        this.isRunningFromRoot = process.env.CWD === this.rootPath;

        // Check asset for env file or fall back to root env if it exists.
        this.envPath = fs.existsSync(rootEnvPath) ? rootEnvPath : null;
        if (!this.isRunningFromRoot) {
            this.envPath = fs.existsSync(assetEnvPath) ? assetEnvPath : this.envPath;
        }

        if (this.envPath) {
            require('dotenv').config({path: this.envPath});
        }

        this.isDevServerRunning = process.env.WEBPACK_DEV_SERVER;

        if (this.isDevServerRunning && this.isRunningFromRoot) {
            throw new Error('Running the dev server is only permitted in individual assets.');
        }

        this.nodeEnv = 'production';
        if (!process.env.NODE_ENV && this.isDevServerRunning) {
            this.nodeEnv = 'development';
        } else if (process.env.NODE_ENV) {
            this.nodeEnv = process.env.NODE_ENV;
        }

        process.env.NODE_ENV = this.nodeEnv;

        this.https = false;

        if (process.env.DEV_SERVER_SSL_KEY && process.env.DEV_SERVER_SSL_CERT) {
            this.https = {
                key: fs.readFileSync(process.env.DEV_SERVER_SSL_KEY),
                cert: fs.readFileSync(process.env.DEV_SERVER_SSL_CERT)
            };
        }

        const devHost = process.env.DEV_SERVER_HOST ? process.env.DEV_SERVER_HOST : 'localhost'
        const devPort = process.env.DEV_SERVER_PORT ? process.env.DEV_SERVER_PORT : '8085'

        this.devServer = {
            contentBase: process.env.DEV_SERVER_CONTENT_BASE ? process.env.DEV_SERVER_CONTENT_BASE : path.join(this.basePath, 'dist'),
            host: devHost,
            port: devPort,
            publicPath: process.env.DEV_SERVER_PUBLIC ? process.env.DEV_SERVER_PUBLIC : (this.https ? 'https' : 'http') + `://${devHost}:${devPort}/`
        };

        // Settings
        this.srcPath = this.basePath + '/src';
        this.distPath = this.basePath + '/dist';
        this.jsFilename = '[name].min.js';

        // Set options from class call
        this.templatesPath = options.templatesPath || path.join(this.rootPath, '/src/templates');
        this.type = options.type || 'asset';
        this.config = options.config || {};
        this.postCssConfig = options.postCssConfig || path.resolve(__dirname, 'postcss.config.js');
        this.removeFiles = options.removeFiles || null;

        if (this.types.indexOf(this.type) === -1) {
            throw 'Type "' + this.type + '" is not a valid config type.';
        }

        return merge(this[this.type](), this.config);
    }

    /**
     * Get dev server options
     * @private
     */
    _devServer() {
        // Find PHP asset bundles
        let files = fs.readdirSync(this.basePath);
        let assetBundleClasses = [];

        for (let i = 0; i < files.length; i++) {
            let filename = path.join(this.basePath, files[i]);
            let stat = fs.lstatSync(filename);
            if (!stat.isDirectory() && filename.indexOf('.php') > 0) {
                let data = fs.readFileSync(filename);

                if (data) {
                    let namespaceRegex = /namespace\s(.*?);/gs;
                    let classNameRegex = /class\s(.*?)\sextends/gs;
                    let m; let n;
                    let namespace = null;
                    let className = null;

                    while ((m = namespaceRegex.exec(data)) !== null) {
                        // This is necessary to avoid infinite loops with zero-width matches
                        if (m.index === namespaceRegex.lastIndex) {
                            namespaceRegex.lastIndex++;
                        }

                        // The result can be accessed through the `m`-variable.
                        m.forEach((match, groupIndex) => {
                            if (groupIndex === 1) {
                                namespace = match;
                            }
                        });
                    }

                    while ((n = classNameRegex.exec(data)) !== null) {
                        // This is necessary to avoid infinite loops with zero-width matches
                        if (n.index === classNameRegex.lastIndex) {
                            classNameRegex.lastIndex++;
                        }

                        // The result can be accessed through the `m`-variable.
                        n.forEach((match, groupIndex) => {
                            if (groupIndex === 1) {
                                className = match;
                            }
                        });
                    }

                    if (namespace && className) {
                        assetBundleClasses.push(namespace + '\\' + className);
                    }
                }
            }
        }

        let response = {
            classes: assetBundleClasses,
            basePath: this.basePath,
            srcPath: this.srcPath,
            envPath: this.envPath,
            distPath: this.distPath,
        }

        return {
            contentBase: this.devServer.contentBase,
            watchContentBase: true,
            disableHostCheck: true,
            headers: {"Access-Control-Allow-Origin": "*"},
            host: this.devServer.host,
            hot: true,
            https: this.https,
            inline: true,
            port: this.devServer.port,
            public: this.devServer.publicPath,
            stats: 'errors-only',
            before: function(app, server, compiler) {
                app.get('/which-asset', function(req, res) {
                    res.json(response);
                });
            }
        };
    }

    /**
     * Base webpack config
     */
    base() {
        const plugins = [
            new ExtraWatchWebpackPlugin({
                dirs: [ this.templatesPath ],
            }),
            new WebpackForceRebuildOnEmitPlugin(),
        ];
        let optimization = {};

        // Only load dotenv plugin if there is a .env file
        if (this.envPath) {
            plugins.push(new Dotenv());
        }

        if (this.removeFiles && typeof this.removeFiles === 'object' && !this.isDevServerRunning) {
            let after = {
                root: this.removeFiles.root || this.distPath,
            };

            if (this.removeFiles.test !== undefined && this.removeFiles.test !== null && Array.isArray(this.removeFiles.test)) {
                let removeRegExpTests = [];
                this.removeFiles.test.forEach(regExp => {
                    removeRegExpTests.push({
                        folder: '.',
                        method: (absPath) => {
                            return new RegExp(regExp, 'm').test(absPath);
                        }
                    });
                });

                after.test = removeRegExpTests;
            }

            if (this.removeFiles.include !== undefined) {
                after.include = this.removeFiles.include;
            }

            plugins.push(new RemovePlugin({
                after: after
            }));
        }

        if (!this.isDevServerRunning) {
            plugins.push(new CleanWebpackPlugin());

            optimization = {
                minimize: true,
                minimizer: [
                    new TerserWebpackPlugin({
                        extractComments: false,
                        parallel: true,
                        terserOptions: {
                            compress: {
                                keep_classnames: true,
                                keep_fnames: true,
                                unused: false,
                            },
                            mangle: false,
                            output: {
                                comments: false,
                            },
                        },
                        test: /\.js(\?.*)?$/i,
                    }),
                    this.nodeEnv === 'production' ? new CssMinimizerPlugin({
                        parallel: true,
                    }) : null,
                ],
            };
        }

        const baseConfig = {
            watch: this.nodeEnv === 'development',
            mode: this.nodeEnv,
            devtool: 'source-map',
            optimization,
            resolve: {
                extensions: ['.wasm', '.ts', '.tsx', '.mjs', '.js', '.json', '.vue'],
            },
            module: {
                rules: [
                    // Typescript
                    {
                        test: /.ts$/,
                        exclude: /(node_modules|bower_components)/,
                        use: {
                            loader: 'ts-loader',
                            options: {
                                configFile: path.resolve(__dirname, './tsconfig.json'),
                            }
                        }
                    },
                    // Babel
                    {
                        test: /.m?js?$/,
                        exclude: /(node_modules|bower_components)/,
                        use: {
                            loader: 'babel-loader',
                            options: {
                                plugins: ['@babel/plugin-syntax-dynamic-import'],
                                presets: ['@babel/preset-env', '@babel/preset-typescript']
                            }
                        }
                    },

                    // GraphQL
                    // https://github.com/graphql/graphql-js/issues/2721#issuecomment-723008284
                    {
                      test: /\.m?js/,
                      resolve: {
                          fullySpecified: false
                      }
                  },
                ]
            },
            plugins,
        };

        return baseConfig;
    }

    /**
     * Asset webpack config
     */
    asset() {
        const assetConfig = {
            context: this.srcPath,
            output: {
                filename: this.jsFilename,
                path: this.distPath,
                publicPath: this.nodeEnv == 'development' ? this.devServer.publicPath : '/',
            },
            devServer: this._devServer(),
            module: {
                rules: [
                    {
                        test: /\.s?[ac]ss$/i,
                        use: [
                            'vue-style-loader',
                            {
                                loader: MiniCssExtractPlugin.loader,
                                options: {
                                    publicPath: './',
                                    esModule: false,
                                }
                            },
                            'css-loader',
                            {
                                loader: 'postcss-loader',
                                options: {
                                    postcssOptions: {
                                        config: this.postCssConfig
                                    },
                                }
                            },
                            {
                                loader: "sass-loader",
                                options: {
                                    // Prefer `dart-sass`
                                    implementation: require("sass"),
                                },
                            },
                        ],
                    },

                    // TODO: put fonts in a separate folder
                    // https://stackoverflow.com/a/66681262
                    {
                        test: /fonts\/[a-zA-Z0-9\-\_]*\.(ttf|woff|svg)$/,
                        type: 'asset/resource',
                    },
                    {
                        test: /\.(jpg|gif|png|svg|ico)$/,
                        type: 'asset/resource',
                        exclude: [
                            path.resolve(this.srcPath, './fonts'),
                        ],
                    },
                ],
            },
            plugins: [
                new MiniCssExtractPlugin({
                    filename: '[name].css',
                    chunkFilename: '[name].css',
                }),
            ]
        };

        return merge(this.base(), assetConfig);
    }

    lib() {
        // Remove placeholder entry file for lib assets
        if (!this.removeFiles) {
            this.removeFiles = {test: []};
        } else if (this.removeFiles && typeof this.removeFiles === 'object' && this.removeFiles.test === undefined) {
            this.removeFiles.test = [];
        }

        this.removeFiles.test.push(/entry/);

        return this.asset();
    }

    /**
     * Vue webpack config
     */
    vue() {
        // TODO: https://webpack.js.org/migrate/5/#clean-up-configuration
        // kill this?
        const optimization = {};
        // const optimization = this.isDevServerRunning ? {} : {
        //     splitChunks: {
        //         name: false,
        //         cacheGroups: {
        //             defaultVendors: {
        //                 test: /[\\/]node_modules[\\/]/,
        //                 name: 'chunk-vendors',
        //                 chunks: 'all'
        //             }
        //         }
        //     }
        // };

        const vueConfig = {
            context: this.srcPath,
            output: {
                filename: this.jsFilename,
                path: this.distPath,
                publicPath: this.devServer.publicPath,
            },
            module: {
                rules: [
                    {
                        test: /\.vue$/i,
                        use: [
                            'vue-loader',
                        ]
                    }
                ]
            },
            devServer: this._devServer(),
            optimization,
            externals: {
                'vue': 'Vue',
                'vue-router': 'VueRouter',
                'vuex': 'Vuex',
                'axios': 'axios'
            },
            plugins: [
                new VueLoaderPlugin(),

                // TODO: shouldn't include this in production
                new webpack.HotModuleReplacementPlugin(),

                new WebpackManifestPlugin({
                    publicPath: '/'
                }),
            ],
        };

        return merge(this.asset(), vueConfig);
    }
}

module.exports = CraftWebpackConfig;
