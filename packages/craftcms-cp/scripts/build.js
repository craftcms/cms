import {build} from 'tsdown';
import ora from 'ora';
import {execSync} from 'node:child_process';
import {mkdir} from 'fs/promises';
import {watch} from 'fs';
import copy from 'recursive-copy';
import {getDistDir, getRootDir, resolveFrom} from './utils.js';
import {join} from 'path';
import {deleteAsync} from 'del';
import createVueWrappers from './generate-vue-wrappers.js';

const spinner = ora({text: '@craftcms/cp', color: 'red'}).start();
const isDeveloping = process.argv.includes('--develop');

async function cleanup() {
  spinner.start('Cleaning up dist');

  await deleteAsync(getDistDir());
  await mkdir(getDistDir(), {recursive: true});

  spinner.succeed();
}

async function generateBundle(config = {}) {
  spinner.start('Building bundle');
  try {
    await build({
      clean: false,
      target: 'es2020',
      entry: {
        cp: './src/index.ts',
        ...(await resolveFrom(
          './src/components/**/!(*.(stories|styles|test)).ts'
        )),
        ...(await resolveFrom('./src/services/**/!(*.(styles|test)).ts')),
        ...(await resolveFrom('./src/utilities/**/!(*.(styles|test)).ts')),
        ...(await resolveFrom('./src/services/**/!(*.(styles|test)).ts')),
      },
      minify: !isDeveloping,
      external: ['lit', '@lion/ui', '@awesome.me/webawesome'],
      format: ['esm'],
      sourcemap: true,
      dts: true,
      ...config,
    });
  } catch (error) {
    spinner.fail();
    console.error(error);
  }

  spinner.succeed();
  return Promise.resolve();
}

async function generateManifest() {
  spinner.start('Generating CEM');

  try {
    execSync('cem analyze --config "custom-elements-manifest.config.mjs"');
  } catch (error) {
    console.error(`\n\n${error.message}`);

    if (!isDeveloping) {
      process.exit(1);
    }
  }

  spinner.succeed();

  return Promise.resolve();
}

async function generateStyles() {
  spinner.start('Copying styles');

  await copy(
    join(getRootDir(), 'src/styles'),
    join(getRootDir(), 'dist/styles'),
    {overwrite: true}
  );

  spinner.succeed();

  if (isDeveloping) {
  }

  return Promise.resolve();
}

async function generateVueWrappers() {
  spinner.start('Generating Vue Wrappers');

  createVueWrappers();
  return Promise.resolve();
}

async function buildAll() {
  try {
    const steps = [
      cleanup,
      generateManifest,
      generateStyles,
      generateVueWrappers,
      generateBundle,
    ];

    for (const step of steps) {
      await step();
    }

    spinner.succeed(`The build is complete`);
  } catch (error) {
    spinner.fail();
    console.error(error);
  }
}

async function runBuild() {
  // Run an initial build
  await buildAll();

  // Then start watching
  if (isDeveloping) {
    await generateBundle({watch: true});
  }

  // watch for style changes
  if (isDeveloping) {
    const stylesDir = join(getRootDir(), 'src/styles');
    console.log(`n👀 Watching for style changes in: ${stylesDir}`);

    let debounceTimer;
    watch(stylesDir, {recursive: true}, (eventType, filename) => {
      if (filename) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
          await generateStyles();
        }, 100);
      }
    });
  }

  // watch for component changes and regenerate manifest
  if (isDeveloping) {
    const componentsDir = join(getRootDir(), 'src/components');
    console.log(`\n👀 Watching for component changes in: ${componentsDir}`);

    let debounceTimer;
    watch(componentsDir, {recursive: true}, (eventType, filename) => {
      if (
        filename &&
        filename.endsWith('.ts') &&
        !filename.includes('.test.') &&
        !filename.includes('.stories.')
      ) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
          await generateManifest();
        }, 100);
      }
    });
  }
}

await runBuild();
