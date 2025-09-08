#!/usr/bin/env node
const path = require('path');
const util = require('util');
const nodeExec = util.promisify(require('child_process').exec);
const spawn = require('child_process').spawn;
const logger = require('./playwright/logger');

(async function main() {
  // Get args
  let [, , ...args] = process.argv;

  // If args is not an array or is empty, exit
  if (!Array.isArray(args) || args.length === 0) {
    logger.warn('Please provide a command.');

    process.exit(1);
  }

  // Set command to the first arg
  const command = args[0];
  args.shift();

  if (command == 'test') {
    logger.start('Running tests…');

    const pre = spawn('/bin/bash', [path.resolve(__dirname, 'ddev/setup.sh')], {
      cwd: path.resolve(__dirname),
      stdio: 'inherit',
    });

    pre.on('close', (code) => {
      const tests = spawn('npx', ['playwright', 'test'].concat(args), {
        stdio: 'inherit',
      });

      tests.on('error', (error) => {
        logger.fatal(error);
      });

      tests.on('close', (code) => {
        const down = spawn(
          '/bin/bash',
          [path.resolve(__dirname, 'ddev/teardown.sh')],
          {
            cwd: path.resolve(__dirname),
            stdio: 'inherit',
          }
        );
      });
    });
  } else if (command == 'boot') {
    logger.start('Booting test container…');
    const boot = spawn(
      '/bin/bash',
      [path.resolve(__dirname, 'ddev/setup.sh')],
      {
        cwd: path.resolve(__dirname),
        stdio: 'inherit',
      }
    );
  } else if (command == 'down') {
    logger.start('Tearing down test container…');
    const down = spawn(
      '/bin/bash',
      [path.resolve(__dirname, 'ddev/teardown.sh')],
      {
        cwd: path.resolve(__dirname),
        stdio: 'inherit',
      }
    );
  }
})().catch((err) => {
  logger.fatal(err);
});
