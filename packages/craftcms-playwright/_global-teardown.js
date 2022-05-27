const craft = require('./_craft');

module.exports = async (config) => {
  console.log('Tearing down');
  await craft.dbRestore();
  await craft.projectConfigRestore();
  await craft.composerRestore();
};
