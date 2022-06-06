#!/usr/bin/env node
const path = require('path');
const util = require('util');
const nodeExec = util.promisify(require('child_process').exec);
const spawn = require('child_process').spawn;

(async function main() {
  // Get args
  let [, , ...args] = process.argv;

  // If args is not an array or is empty, exit
  if (!Array.isArray(args) || args.length === 0) {
    process.stdout.write('Please provide a command.');
    process.stdout.write('\n');
    process.exit(1);
  }

  // Set command to the first arg
  const command = args[0];
  args.shift();

  if (command == 'test') {
    process.stdout.write('Running tests…');
    process.stdout.write('\n');
    const pre = spawn(
      '/bin/bash',
      [path.resolve(__dirname, 'scripts/pre.sh')],
      {
        cwd: path.resolve(__dirname),
        stdio: 'inherit',
      }
    );

    pre.on('close', (code) => {
      const tests = spawn('npx', ['playwright', 'test'], {
        stdio: 'inherit',
      });

      tests.on('error', (error) => {
        console.error(error);
      });

      tests.on('close', (code) => {
        const down = spawn(
          '/bin/bash',
          [path.resolve(__dirname, 'scripts/post.sh')],
          {
            cwd: path.resolve(__dirname),
            stdio: 'inherit',
          }
        );
      });
    });
  } else if (command == 'boot') {
    const boot = spawn(
      '/bin/bash',
      [path.resolve(__dirname, 'scripts/pre.sh')],
      {
        cwd: path.resolve(__dirname),
        stdio: 'inherit',
      }
    );
  } else if (command == 'down') {
    const down = spawn(
      '/bin/bash',
      [path.resolve(__dirname, 'scripts/post.sh')],
      {
        cwd: path.resolve(__dirname),
        stdio: 'inherit',
      }
    );
  }
})().catch((err) => {
  console.log(err);
});
