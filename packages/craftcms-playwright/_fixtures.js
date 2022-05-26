const base = require('@playwright/test');
const craft = require('./_craft');

const cleanAll = [
  async ({}, use) => {
    await use();
    await craft.dbRestore();
    await craft.projectConfigRestore();
    await craft.composerRestore();
  },
  {timeout: 120000},
];

const cleanDb = async ({}, use) => {
  await use();
  const stuff = await craft.dbRestore();
};

const cleanProjectConfig = async ({}, use) => {
  await use();
  await craft.projectConfigRestore();
};

const cleanComposer = async ({}, use) => {
  await use();
  await craft.composerRestore();
};

module.exports = {
  test: base.test.extend({
    cleanAll,
    cleanComposer,
    cleanDb,
    cleanProjectConfig,
  }),
  expect: base.expect,
};
