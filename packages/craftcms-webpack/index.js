const glob = require('glob');
const path = require('path');
const { merge } = require('webpack-merge');
const dotenv = require('dotenv');
const fs = require('fs');

// Where webpack-cli was run from
const rootPath = path.resolve('./');

// const callerPath = path.basename(getCallerFile());
// Fix issue with monorepo and some plugins
// https://github.com/jantimon/html-webpack-plugin/issues/1451#issuecomment-712581727
const _require = id => require(require.resolve(id, { paths: [require.main.path] }));

const fetchConfigs = (
  globPattern = 'src/web/assets/*/webpack.config.js',
  options = {
    cwd: module.parent.path,
  },
) => glob.sync(globPattern, options).map(match => {
  return require(path.resolve(options.cwd, match));
});

const types = {
  asset(){
    return {};
  },
  base() {
    return {};
  },
  lib() {
    return {};
  },
  vue() {
    return {};
  },
}

const configFactory = ({
  cwd,
  type = 'asset',
  config = {},
  templatesPath = path.join(rootPath, '/src/templates'),
  postcssConfig = getFirstExistingPath([
    path.resolve(cwd, 'postcss.config.js'),
    path.resolve(__dirname, 'postcss.config.js'),
  ]),
  removeFiles = null,
}) => {

  const envFilePath = getFirstExistingPath([
    path.join(cwd, '.env'),
    path.join(rootPath, '.env'),
  ]);

  if (envFilePath) {
    dotenv.config({path: envFilePath})
  }

  return merge(types[type](), config);
};

const getFirstExistingPath = (paths = []) => {
  return paths.find(path => {
    return fs.existsSync(path);
  });
}

module.exports = {
  configFactory,
  fetchConfigs,
};
