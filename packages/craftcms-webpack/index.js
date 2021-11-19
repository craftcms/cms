const glob = require('glob');
const path = require('path');
const fetchConfigs = (
  globPattern = path.resolve(module.parent.path, 'src/web/assets/**/webpack.config.js'),
  options = {},
) => glob.sync(globPattern, options).map(path => require(path));

module.exports ={
  ConfigFactory: require('./ConfigFactory'),
  fetchConfigs,
};
