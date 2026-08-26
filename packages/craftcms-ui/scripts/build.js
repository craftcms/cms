import {build} from 'vite-plus/pack';
import ora from 'ora';
import {execSync} from 'node:child_process';
import {mkdir, readFile} from 'fs/promises';
import {watch} from 'fs';
import copy from 'recursive-copy';
import {globby} from 'globby';
import {getDistDir, getRootDir, resolveFrom} from './utils.js';
import {dirname, join, relative, resolve} from 'path';
import {deleteAsync} from 'del';
import createVueWrappers from './generate-vue-wrappers.js';
import createColors from './generate-colors.js';
import createTailwindTheme from './generate-tailwind.js';

const spinner = ora({text: '@craftcms/ui', color: 'red'}).start();
const isDeveloping = process.argv.includes('--develop');

async function cleanup() {
  // In develop mode, rebuild in place: the root Vite dev server starts
  // alongside this watcher, and deleting dist would make its dependency scan
  // fail while the initial rebuild runs.
  if (isDeveloping) {
    await mkdir(getDistDir(), {recursive: true});
    return;
  }

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
        actions: './src/actions/index.ts',
        ...(await resolveFrom(
          './src/components/**/!(*.(stories|styles|test)).ts'
        )),
        ...(await resolveFrom('./src/services/**/!(*.(styles|test)).ts')),
        ...(await resolveFrom('./src/utilities/**/!(*.(styles|test)).ts')),
        ...(await resolveFrom('./src/services/**/!(*.(styles|test)).ts')),
        ...(await resolveFrom('./src/factory/**/!(*.(styles|test)).ts')),
      },
      minify: !isDeveloping,
      external: ['lit', '@lion/ui'],
      format: ['esm'],
      sourcemap: true,
      dts: true,
      ...config,
    });
  } catch (error) {
    spinner.fail();
    console.error(error);

    // Nothing was emitted, so the steps after this one would only inspect a
    // stale dist. Abort the run instead of reporting success.
    throw error;
  }

  spinner.succeed();
  return Promise.resolve();
}

/** Root-level content-hashed chunk, e.g. `popover-BnF9P8Hy.d.mts`. */
function isChunk(file) {
  const rel = relative(getDistDir(), file);
  return !rel.includes('/') && /-[\w-]{8}\.(mjs|d\.mts)$/.test(rel);
}

/** Relative specifiers a bundle references, as absolute paths. */
async function referencesOf(file) {
  const contents = await readFile(file, 'utf8');
  const found = [];

  for (const match of contents.matchAll(
    /(?:from|import)\s*\(?\s*["']([^"']+)["']/g
  )) {
    const specifier = match[1];
    if (!specifier.startsWith('.')) {
      continue;
    }

    const target = resolve(dirname(file), specifier);
    found.push(target);

    // Declarations import their runtime siblings — `./x.mjs` from a `.d.mts`
    // resolves types to `./x.d.mts`, so keep both ends of the pair alive.
    if (target.endsWith('.mjs')) {
      found.push(target.replace(/\.mjs$/, '.d.mts'));
    }
  }

  return found;
}

/**
 * Drop hashed chunks left behind by earlier develop-mode rebuilds.
 *
 * `cleanup()` deliberately leaves dist in place while developing, so each
 * rebuild adds a fresh set of content-hashed chunks without retiring the ones
 * it replaced. That is mostly harmless noise, but it also means a chunk can
 * outlive the build that wrote it and stay referenced afterwards — which is how
 * a bad declaration survives long enough to break consumers. Sweep anything no
 * longer reachable from the entry points.
 */
async function pruneOrphanedChunks() {
  spinner.start('Pruning stale chunks');

  const dist = getDistDir();
  const all = await globby([`${dist}/**/*.mjs`, `${dist}/**/*.d.mts`]);
  const known = new Set(all);
  const reachable = new Set();
  const queue = all.filter((file) => !isChunk(file));

  while (queue.length) {
    const file = queue.pop();
    if (reachable.has(file)) {
      continue;
    }
    reachable.add(file);

    for (const reference of await referencesOf(file)) {
      if (known.has(reference) && !reachable.has(reference)) {
        queue.push(reference);
      }
    }
  }

  const orphans = all.filter((file) => isChunk(file) && !reachable.has(file));
  if (orphans.length) {
    await deleteAsync([...orphans, ...orphans.map((file) => `${file}.map`)]);
  }

  spinner.succeed(
    orphans.length
      ? `Pruned ${orphans.length} stale chunk${orphans.length === 1 ? '' : 's'}`
      : undefined
  );

  return Promise.resolve();
}

/**
 * Refuse to ship a mixin base type that degraded to `any`.
 *
 * `@lion/ui` declares its overlay and form-core hosts at `@lion/ui/types/*.js`,
 * a subpath its own `exports` map never exposes, so those hosts already resolve
 * to error types here. That much is survivable: `typeof LitElement` stays in
 * the intersection and keeps carrying the DOM prototype chain. What is not
 * survivable is a build that additionally fails to resolve `lit` — typically
 * one racing an `npm install`. The emitter then collapses the entire base to
 * `any`, every component extending it stops being an HTMLElement, and the
 * damage surfaces as type errors in consumer code rather than here. Left alone
 * this is emitted silently, with a zero exit code.
 */
async function assertDeclarationsResolved() {
  spinner.start('Verifying declarations');

  const degraded = [];

  for (const file of await globby(`${getDistDir()}/**/*.d.mts`)) {
    const contents = await readFile(file, 'utf8');

    for (const [, name] of contents.matchAll(
      /declare const (\w+_base): any/g
    )) {
      degraded.push(`${name} in ${relative(getDistDir(), file)}`);
    }
  }

  if (degraded.length) {
    spinner.fail('Degraded mixin base types emitted');

    for (const entry of degraded) {
      console.error(`  - ${entry}`);
    }

    console.error(
      '\nThese bases resolved to `any`, so their subclasses are no longer' +
        '\nHTMLElements and consumers will fail to typecheck against them.' +
        '\nUsually caused by a build racing an `npm install`. Rebuild from a' +
        '\nclean dist once installs have settled:\n\n  rm -rf dist && npm run build\n'
    );

    if (!isDeveloping) {
      process.exit(1);
    }

    return Promise.resolve();
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
  spinner.succeed();
  return Promise.resolve();
}

async function generateColors() {
  spinner.start('Generating Colors');
  await createColors();
  spinner.succeed();
  return Promise.resolve();
}

async function generateTailwindTheme() {
  spinner.start('Generating Tailwind Theme');
  await createTailwindTheme();
  spinner.succeed();
  return Promise.resolve();
}

async function buildAll() {
  try {
    const steps = [
      cleanup,
      generateManifest,
      generateStyles,
      generateVueWrappers,
      generateColors,
      generateTailwindTheme,
      generateBundle,
      pruneOrphanedChunks,
      assertDeclarationsResolved,
    ];

    for (const step of steps) {
      await step();
    }

    spinner.succeed(`The build is complete`);
  } catch (error) {
    spinner.fail('The build failed');
    console.error(error);

    // In develop mode the watchers still start, so a bad edit can be fixed in
    // place. A one-shot build has to exit non-zero or `build:all` will carry on
    // into the legacy bundles and fail there with unresolvable imports instead.
    if (!isDeveloping) {
      process.exitCode = 1;
    }
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
          await generateVueWrappers();
        }, 100);
      }
    });
  }
}

await runBuild();
