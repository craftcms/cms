#!/usr/bin/env node
const path = require('path');
const spawn = require('child_process').spawn;
const logger = require('./playwright/logger');

(async function main() {
  // Get args
  let [, , ...args] = process.argv;
  const baseDir = path.dirname(require.main.filename);
  const pkgRoot = path.resolve(baseDir, '../');

  // If args is not an array or is empty, exit
  if (!Array.isArray(args) || args.length === 0) {
    logger.warn('Please provide a command.');

    process.exit(1);
  }

  // Set command to the first arg
  const command = args[0];
  args.shift();

  if (command === 'test') {
    logger.start('Running tests…');

    const pre = spawn('/bin/bash', [path.resolve(pkgRoot, 'ddev/setup.sh')], {
      cwd: path.resolve(pkgRoot),
      stdio: 'inherit',
    });

    pre.on('close', () => {
      const tests = spawn('npx', ['playwright', 'test'].concat(args), {
        stdio: 'inherit',
      });

      tests.on('error', (error) => {
        logger.fatal(error);
      });

      tests.on('close', () => {
        spawn('/bin/bash', [path.resolve(pkgRoot, 'ddev/teardown.sh')], {
          cwd: path.resolve(pkgRoot),
          stdio: 'inherit',
        });
      });
    });
  } else if (command === 'boot') {
    logger.start('Booting test container…');
    spawn('/bin/bash', [path.resolve(pkgRoot, 'ddev/setup.sh')], {
      cwd: path.resolve(pkgRoot),
      stdio: 'inherit',
    });
  } else if (command === 'down') {
    logger.start('Tearing down test container…');
    spawn('/bin/bash', [path.resolve(pkgRoot, 'ddev/teardown.sh')], {
      cwd: path.resolve(pkgRoot),
      stdio: 'inherit',
    });
  }
})().catch((err) => {
  logger.fatal(err);
});
