const {getConfigs} = require('@craftcms/webpack');
const garnishConfig = require('@craftcms/garnish');

module.exports = [
  garnishConfig,
  ...getConfigs()
];
