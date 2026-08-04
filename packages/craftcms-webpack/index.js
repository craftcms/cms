// Fix issue with monorepo and some plugins
// https://github.com/jantimon/html-webpack-plugin/issues/1451#issuecomment-712581727
const _require = (id) =>
  require(require.resolve(id, {paths: [require.main.path]}));

const path = require('path');
const glob = require('glob');
const {merge} = require('webpack-merge');
const dotenv = require('dotenv');
const fs = require('fs');
const yargs = require('yargs/yargs');
const {hideBin} = require('yargs/helpers');
const argv = yargs(hideBin(process.argv)).argv;
const Dotenv = require('dotenv-webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const {CleanWebpackPlugin} = require('clean-webpack-plugin');
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const {WebpackManifestPlugin} = _require('webpack-manifest-plugin');
const JsonMinimizerPlugin = require('json-minimizer-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const requestedConfig = argv['config-name'] ?? null;

/**
 * Returns the first existing file based on a list of paths.
 *
 * @param {string[]} paths
 * @returns {string|undefined}
 */
const getFirstExistingPath = (paths = []) => {
  return paths.find((path) => {
    return fs.existsSync(path);
  });
};

/**
 * Returns and array of webpack configs.
 *
 * @param {string} globPattern
 * @param {Object} options
 * @param {string} options.cwd Path where the glob should be executed from.
 */
const getConfigs = (
  globPattern = 'src/web/assets/*/webpack.config.js',
  options = {
    cwd: module.parent.path,
  }
) =>
  glob
    .sync(globPattern, options)
    .filter((match) => {
      // filter out unnecessary configs if we are only trying to build or serve one asset
      return (
        !requestedConfig ||
        requestedConfig == path.basename(path.dirname(match))
      );
    })
    .map((match) => {
      return require(path.resolve(options.cwd, match));
    });

/**
 * Return a webpack config object.
 *
 * @param {string} context
 * @param {string|null} type
 * @param {Object} config
 * @param {string[]} watchPaths
 * @param {string} postcssConfig
 * @returns {Object}
 */
const getConfig = ({context, type, watchPaths, postcssConfig, config = {}}) => {
  // Where webpack-cli was run from
  const rootPath = path.resolve('./');
  const currentConfigName = argv['config-name'] || null;
  const isDevServerRunning = path.basename(argv['$0']) === 'webpack-dev-server';

  if (!context) {
    throw new Error(`The [context] argument is required.`);
  }

  if (isDevServerRunning && !currentConfigName) {
    throw new Error(
      `Running the dev server is only permitted in individual bundles.`
    );
  }

  const configName = config.name || path.basename(context);

  if (!watchPaths) {
    watchPaths = [
      path.join(rootPath, 'src/templates'),
      path.join(context, 'dist'),
    ];
  }

  if (!postcssConfig) {
    postcssConfig = getFirstExistingPath([
      path.resolve(context, 'postcss.config.js'),
      path.resolve(__dirname, 'postcss.config.js'),
    ]);
  }

  const applyDotEnv = ({context, configName, currentConfigName, rootPath}) => {
    const isCurrentConfig =
      currentConfigName && configName === currentConfigName;
    const envFilePath = getFirstExistingPath(
      [
        isCurrentConfig && path.join(context, '.env'),
        path.join(rootPath, '.env'),
      ].filter(Boolean)
    );

    if (envFilePath) {
      dotenv.config({path: envFilePath});
      return envFilePath;
    }

    return false;
  };

  const getDevServer = ({context, watchPaths}) => {
    // Find PHP asset bundles
    let files = fs.readdirSync(context);
    let assetBundleClasses = [];

    for (let i = 0; i < files.length; i++) {
      let filename = path.join(context, files[i]);
      let stat = fs.lstatSync(filename);
      if (!stat.isDirectory() && filename.indexOf('.php') > 0) {
        let data = fs.readFileSync(filename);

        if (data) {
          let namespaceRegex = /namespace\s(.*?);/gs;
          let classNameRegex = /class\s(.*?)\sextends/gs;
          let m;
          let n;
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

    const https =
      process.env.DEV_SERVER_SSL_KEY && process.env.DEV_SERVER_SSL_CERT
        ? {
            key: fs.readFileSync(process.env.DEV_SERVER_SSL_KEY),
            cert: fs.readFileSync(process.env.DEV_SERVER_SSL_CERT),
          }
        : false;
    const host = process.env.DEV_SERVER_HOST || 'localhost';
    const port = process.env.DEV_SERVER_PORT || '8085';
    const scheme = https ? 'https' : 'http';
    const publicPath =
      process.env.DEV_SERVER_PUBLIC || `${scheme}://${host}:${port}/`;

    return {
      host,
      https,
      port,
      allowedHosts: 'all',
      devMiddleware: {
        publicPath,
      },
      headers: {'Access-Control-Allow-Origin': '*'},
      hot: true,
      client: {
        overlay: {
          errors: true,
          warnings: false,
        },
      },
      static: watchPaths.map((directory) => ({
        directory,
        watch: true,
      })),
      onBeforeSetupMiddleware: function (devServer) {
        devServer.app.get('/which-asset', function (req, res) {
          res.json({
            classes: assetBundleClasses,
            context,
          });
        });
      },
    };
  };

  const getBaseConfig = ({
    context,
    configName,
    currentConfigName,
    isDevServerRunning,
    watchPaths,
    postcssConfig,
  }) => {
    // Apply .env file first if applicable
    const dotenvResult = applyDotEnv({
      context,
      configName,
      currentConfigName,
      rootPath,
    });

    const config = {
      name: configName,
      context: path.join(context, 'src'),
      entry: {},
      output: {
        filename: '[name].js',
        path: path.join(context, 'dist'),
      },
      optimization: {},
      devServer: getDevServer({context, watchPaths}),
      devtool: 'source-map',
      resolve: {
        extensions: ['.wasm', '.ts', '.tsx', '.mjs', '.js', '.json', '.vue'],
      },
      module: {
        rules: [
          {
            test: /.ts$/,
            exclude: /(node_modules|bower_components)/,
            use: {
              loader: 'ts-loader',
              options: {
                configFile: path.resolve(__dirname, './tsconfig.json'),
              },
            },
          },
          {
            test: /.m?js?$/,
            exclude: /(node_modules|bower_components)/,
            use: {
              loader: 'babel-loader',
              options: {
                plugins: ['@babel/plugin-syntax-dynamic-import'],
                presets: ['@babel/preset-env', '@babel/preset-typescript'],
              },
            },
          },

          // graphiql
          // https://github.com/graphql/graphql-js/issues/2721#issuecomment-723008284
          {
            test: /\.m?js/,
            resolve: {
              fullySpecified: false,
            },
          },
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
                },
              },
              'css-loader',
              {
                loader: 'postcss-loader',
                options: {
                  postcssOptions: {
                    config: postcssConfig,
                  },
                },
              },
              {
                loader: 'sass-loader',
                options: {
                  // Prefer `dart-sass`
                  implementation: require('sass'),
                },
              },
            ],
          },
          {
            test: /fonts\/[a-zA-Z0-9\-\_]*\.(ttf|woff|woff2|svg|eot)$/,
            type: 'asset/resource',
            generator: {
              filename: 'fonts/[name][ext][query]',
            },
          },
          {
            test: /\.(jpg|gif|png|svg|ico)$/,
            type: 'asset/resource',
            exclude: [path.resolve(context, './fonts')],
            generator: {
              filename: '[path][name][ext][query]',
            },
          },
        ],
      },
      plugins: [
        new MiniCssExtractPlugin({
          filename: 'css/[name].css',
          chunkFilename: 'css/[name].css',
        }),
      ],
      externals: {
        jquery: 'jQuery',
        axios: 'axios',
        fabric: 'fabric',
        'element-resize-detector': 'elementResizeDetectorMaker',
        garnishjs: 'Garnish',
        'iframe-resizer': 'iFrameResize',
        picturefill: 'picturefill',
        xregexp: 'XRegExp',
      },
    };

    if (dotenvResult) {
      config.plugins.push(new Dotenv({path: dotenvResult}));
    }

    if (!isDevServerRunning) {
      config.plugins.push(new CleanWebpackPlugin());
    }

    if (process.env.NODE_ENV === 'production') {
      config.optimization.minimize = true;
      config.optimization.minimizer = [
        `...`,
        new JsonMinimizerPlugin(),

        // `MiniCssExtractPlugin` does this for us for modules,
        // but this covers any assets, e.g. `CopyWebpackPlugin`.
        new CssMinimizerPlugin(),
      ];
    }

    return config;
  };

  const getTypeConfig = (type) => {
    const types = {
      vue: {
        module: {
          rules: [
            {
              test: /\.vue$/i,
              use: ['vue-loader'],
            },
          ],
        },
        externals: {
          vue: 'Vue',
          'vue-router': 'VueRouter',
          vuex: 'Vuex',
          axios: 'axios',
        },
        plugins: [
          new VueLoaderPlugin(),
          new WebpackManifestPlugin({
            publicPath: '/',
          }),
        ],
      },
    };

    const config = types[type];

    if (type && !config) {
      throw `Type [${type}] is not a valid config type. Must be one of [${Object.keys(
        types
      ).join(', ')}].`;
    }

    return config || {};
  };

  return merge(
    getBaseConfig({
      context,
      configName,
      currentConfigName,
      isDevServerRunning,
      watchPaths,
      postcssConfig,
    }),
    getTypeConfig(type),
    config
  );
};

module.exports = {
  getConfig,
  getConfigs,
};
