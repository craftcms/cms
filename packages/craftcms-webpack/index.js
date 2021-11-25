const glob = require("glob");
const path = require("path");
const getConfig = require('./get-config');

const getConfigs = (
  globPattern = "src/web/assets/*/webpack.config.js",
  options = {
    cwd: module.parent.path,
  }
) => glob.sync(globPattern, options).map((match) => {
  return require(path.resolve(options.cwd, match));
});

module.exports = {
  getConfig,
  getConfigs,
};
