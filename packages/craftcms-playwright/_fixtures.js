const {exec} = require('child_process');
const path = require('path');
const base = require('@playwright/test');

module.exports = {
  test: base.test.extend({
    setupDb: [
      async ({}, use) => {
        console.log('Setup DB');
        await use();
      },
      {auto: true},
    ],

    cleanseDb: [
      async ({}, use) => {
        await use();
        console.log('Cleanse DB');
      },
      {auto: true},
    ],
  }),

  expect: base.expect,
};
