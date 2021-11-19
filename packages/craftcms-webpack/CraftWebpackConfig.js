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
const yargs = require('yargs/yargs')
const { hideBin } = require('yargs/helpers')
const argv = yargs(hideBin(process.argv)).argv

// Plugins
const { WebpackManifestPlugin } = _require('webpack-manifest-plugin');
const ParentModule = require('parent-module');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const {CleanWebpackPlugin} = require('clean-webpack-plugin');
const Dotenv = require('dotenv-webpack');
const RemovePlugin = require('remove-files-webpack-plugin');

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

        this.rootPath = path.resolve('./');
        this.basePath = path.dirname(ParentModule());
        this.configName = path.basename(this.basePath);
        this.isDevServerRunning = path.basename(argv['$0']) === 'webpack-dev-server';
        this.requestedConfig = argv['config-name'] || null;
        this.isRequestedConfig = this.requestedConfig && path.basename(this.basePath) === this.requestedConfig;

        const rootEnvPath = path.resolve(this.rootPath, './.env');
        const assetEnvPath = path.resolve(this.basePath, './.env');

        this.envPath = fs.existsSync(rootEnvPath) ? rootEnvPath : null;

        if (this.isRequestedConfig && fs.existsSync(assetEnvPath)) {
            this.envPath = assetEnvPath;
        }

        if (fs.existsSync(this.envPath)) {
            require('dotenv').config({path: this.envPath});
        }

        if (this.isDevServerRunning && !this.configName) {
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
            allowedHosts: "all",
            devMiddleware: {
                publicPath: this.devServer.publicPath,
            },
            headers: {"Access-Control-Allow-Origin": "*"},
            host: this.devServer.host,
            hot: true,
            https: this.https,
            port: this.devServer.port,
            client: {
                overlay: {
                  errors: true,
                  warnings: false,
                },
            },
            static: [
                {
                    directory: this.devServer.contentBase,
                    watch: true,
                },
                {
                    directory: this.templatesPath,
                    watch: true,
                },
            ],
            onBeforeSetupMiddleware: function(devServer) {
                devServer.app.get('/which-asset', function(req, res) {
                    res.json(response);
                });
            }
        };
    }

    /**
     * Base webpack config
     */
    base() {
        const plugins = [];
        let optimization = {};

        // Only load dotenv plugin if there is a .env file
        if (this.envPath) {
            plugins.push(new Dotenv());
        }

        if (this.removeFiles && typeof this.removeFiles === 'object' && !this.isDevServerRunning) {
            let after = {
                root: this.removeFiles.root || this.distPath,
                log: false,
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
                after: after,
            }));
        }

        if (!this.isDevServerRunning) {
            plugins.push(new CleanWebpackPlugin());

            optimization.minimize = true;
        }

        const baseConfig = {
            name: this.configName,
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

                    // graphiql
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

                                    // backing up from dist
                                    publicPath: '../',

                                    // Workaround for css imports/vue
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
                    {
                        test: /fonts\/[a-zA-Z0-9\-\_]*\.(ttf|woff|svg)$/,
                        type: 'asset/resource',
                        generator: {
                            filename: 'fonts/[name][ext][query]'
                        }
                    },
                    {
                        test: /\.(jpg|gif|png|svg|ico)$/,
                        type: 'asset/resource',
                        exclude: [
                            path.resolve(this.srcPath, './fonts'),
                        ],
                        generator: {
                            filename: '[path][name][ext][query]'
                        }
                    },
                ],
            },
            plugins: [
                new MiniCssExtractPlugin({
                    filename: 'css/[name].css',
                    chunkFilename: 'css/[name].css',
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
        const plugins = [
            new VueLoaderPlugin(),
            new WebpackManifestPlugin({
                publicPath: '/'
            }),
        ];

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
            externals: {
                'vue': 'Vue',
                'vue-router': 'VueRouter',
                'vuex': 'Vuex',
                'axios': 'axios'
            },
            plugins
        };

        return merge(this.asset(), vueConfig);
    }
}

module.exports = CraftWebpackConfig;
