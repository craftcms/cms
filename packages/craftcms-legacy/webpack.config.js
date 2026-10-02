const {getConfigs} = require('@craftcms/webpack');

module.exports = getConfigs(
  '{./**/*/webpack.config.js,../../yii2-adapter/legacy/web/assets/xregexp/webpack.config.cjs}'
);
