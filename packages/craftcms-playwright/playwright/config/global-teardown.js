const {Setup} = require('./../fixtures/setup');

module.exports = async (config) => {
  console.log('Tearing down');

  const setup = new Setup();
  await setup.dbRestore();
  await setup.projectConfigRestore();
  await setup.composerRestore();
};
