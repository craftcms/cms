/**
 * `pnpm run build`: builds @craftcms/ui and the CP bundle, overlapping
 * everything that doesn't depend on something else.
 *
 *   generate PHP types ───┐
 *   format changed files ─┼─▶ CP bundle ──────────────────────────────┐
 *                         │     ▲ its @craftcms/ui imports wait here   │
 *                         └─▶ @craftcms/ui: clean ─┬─▶ runtime ────────┤
 *                                                  └─▶ declarations ───┴─▶ done
 *
 * The CP bundle runs in this process, so it can start right away and hold
 * back only its imports from @craftcms/ui until that package's runtime is
 * written; most of the bundling overlaps the package build. The other steps
 * run as child processes directly rather than through `pnpm run`, whose
 * start-up time would otherwise make up a good part of the build.
 */
import {execFileSync, spawn} from 'node:child_process';
import {readFileSync} from 'node:fs';
import {availableParallelism} from 'node:os';
import path from 'node:path';
import readline from 'node:readline';
import {fileURLToPath} from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const ui = path.join(root, 'packages/craftcms-ui');

const scriptsOf = (dir) =>
  JSON.parse(readFileSync(path.join(dir, 'package.json'), 'utf8')).scripts;
const rootScripts = scriptsOf(root);
const uiScripts = scriptsOf(ui);

// Copied now, since the CP bundle below sets variables for this process only.
const childEnv = {
  ...process.env,
  PATH: [ui, root]
    .map((dir) => path.join(dir, 'node_modules/.bin'))
    .concat(process.env.PATH ?? '')
    .join(path.delimiter),
};

/**
 * Runs a shell command, or an argv array without a shell.
 */
function run(label, command, cwd = root) {
  const [file, ...args] = Array.isArray(command) ? command : [command];
  const child = spawn(file, args, {
    cwd,
    shell: !Array.isArray(command),
    stdio: ['ignore', 'pipe', 'pipe'],
    env: childEnv,
  });

  for (const [stream, out] of [
    [child.stdout, process.stdout],
    [child.stderr, process.stderr],
  ]) {
    readline
      .createInterface({input: stream})
      .on('line', (line) => out.write(`[${label}] ${line}\n`));
  }

  return new Promise((resolve, reject) => {
    child.on('error', reject);
    child.on('close', (code) =>
      code === 0
        ? resolve()
        : reject(new Error(`${label} exited with code ${code}`))
    );
  });
}

/**
 * `vp build`, in this process so it can wait on the steps above mid-build.
 */
async function bundleCp({sourcesReady, uiReady}) {
  // Generated above already, so the Vite plugins needn't run it again.
  process.env.CRAFT_SKIP_PHP_CODEGEN = '1';

  const {build} = await vitePlus;

  await build({
    root,
    plugins: [
      {
        name: 'craftcms-build-order',
        enforce: 'pre',
        buildStart: () => sourcesReady,
        resolveId: {
          filter: {id: /^@craftcms\/ui(\/|$)/},
          handler: async () => {
            await uiReady;
            return null;
          },
        },
        // Tailwind resolves CSS imports itself, past `resolveId`, and cp.css
        // imports @craftcms/ui's styles. Holding back every stylesheet is
        // simpler than finding the ones that do, and they're a small part of
        // the bundle.
        load: {
          filter: {id: /\.(css|scss)($|\?)|[?&]type=style/},
          handler: async () => {
            await uiReady;
            return null;
          },
        },
      },
    ],
  });
}

/**
 * Files under `dirs` that differ from HEAD, or null when git can't tell.
 */
function changedFiles(dirs) {
  const git = (...args) =>
    execFileSync('git', args, {cwd: root, encoding: 'utf8'})
      .split('\0')
      .filter(Boolean);

  try {
    return [
      ...new Set([
        ...git(
          'diff',
          '--name-only',
          '-z',
          '--diff-filter=d',
          'HEAD',
          '--',
          ...dirs
        ),
        ...git(
          'ls-files',
          '-z',
          '--others',
          '--exclude-standard',
          '--',
          ...dirs
        ),
      ]),
    ];
  } catch {
    return null;
  }
}

/*
The build formats what it bundles, as `fix-prettier` and the UI package's
`format` script do. CI fails unformatted commits, so only files that differ
from HEAD can need it: format just those, and fall back to both scripts
outside a git checkout. Formatting doesn't change what gets bundled, but a
formatted file must be written before the bundler reads it.
 */
const uiSrc = path.relative(root, path.join(ui, 'src'));
const changed = changedFiles(['resources', uiSrc]);

let uiFormatted;
let resourcesFormatted;

if (changed === null) {
  uiFormatted = run('ui', uiScripts.format, ui);

  // Formatting resources only has to finish before the CP bundle reads them,
  // while the UI package's formatting holds up its build, so leave most
  // cores to that.
  resourcesFormatted = run(
    'fix-prettier',
    `${rootScripts['fix-prettier']} --threads=${Math.ceil(availableParallelism() / 4)}`
  );
} else {
  uiFormatted = resourcesFormatted = changed.length
    ? run('fmt', [
        process.execPath,
        path.join(root, 'node_modules/vite-plus/bin/vp'),
        'fmt',
        '--no-error-on-unmatched-pattern',
        ...changed,
      ])
    : Promise.resolve();
}

const uiPrepared = uiFormatted.then(() =>
  run('ui', `${uiScripts.build} --prepare`, ui)
);
const uiRuntime = uiPrepared.then(() =>
  run('ui', `${uiScripts.build} --runtime`, ui)
);
const uiTypes = uiPrepared.then(() =>
  run('ui:types', `${uiScripts.build} --types`, ui)
);

const codegen = Promise.all([
  run('generate:types', rootScripts['generate:types']),
  run('generate:wayfinder', rootScripts['generate:wayfinder']),
]);

// Loading the Vite config needs the generated code, so wait for that first,
// but get Vite itself loaded in the meantime.
const vitePlus = import('vite-plus');
const cp = codegen.then(() =>
  bundleCp({sourcesReady: resourcesFormatted, uiReady: uiRuntime})
);

try {
  await Promise.all([uiRuntime, codegen, resourcesFormatted, cp, uiTypes]);
} catch (error) {
  console.error(error.message);
  process.exitCode = 1;
}
