const {Setup} = require('./../fixtures/setup');

module.exports = async (config) => {
  const setup = new Setup();
  await setup.dbRestore();
  await setup.projectConfigRestore();
  await setup.composerRestore();
};
