const {getConfigs} = require('@craftcms/webpack');
const pkgDir = require('pkg-dir');

const configs = getConfigs('bundles/**/*/webpack.config.js');

const root = pkgDir.sync();
const outBase = `${root}/../../resources/build/legacy`;

const modifiedConfigs = configs.map((config) => {
  return {
    ...config,
    output: {
      ...config.output,
      path: `${outBase}/${config.name}/dist`,
    },
  };
});

module.exports = modifiedConfigs;
