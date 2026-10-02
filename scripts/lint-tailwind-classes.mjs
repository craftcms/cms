import {execFileSync} from 'node:child_process';
import {readFileSync} from 'node:fs';
import {extname, resolve} from 'node:path';
import {__unstable__loadDesignSystem} from '@tailwindcss/node';
import {Scanner} from '@tailwindcss/oxide';

// Lints the Tailwind classes in the CP's templates, the way stylelint lints
// its CSS:
//
// - Errors on physical utilities (ml-*, text-left, rounded-bl-*, …). The CP
//   has to work in RTL languages too, so use the logical equivalent.
// - Warns on numeric spacing (p-2, gap-4, …) that has a named step (p-md,
//   gap-lg, …), so spacing follows the --c-spacing-* scale.
//
// Usage: node scripts/lint-tailwind-classes.mjs [--quiet] [--diff <ref>] [--staged] [files...]
//
// With no files it checks everything under SOURCE_DIRS. --quiet drops the
// warnings; --diff drops the ones on lines that haven't changed since <ref>, so
// CI only nudges about spacing in new code. --staged does the same for what's
// staged, reading the staged content, for the pre-commit hook. Add
// `lint-tailwind-ignore` to a line, or the line above it, to skip it.

const SOURCE_DIRS = [
  'resources/js',
  'resources/templates',
  'resources/views',
  'workbench/resources',
];
const EXTENSIONS = new Set(['.vue', '.ts', '.js', '.twig', '.php', '.html']);
const IGNORED_PATHS = [
  'resources/js/generated/',
  'resources/js/actions/',
  'resources/js/routes/',
  'resources/js/wayfinder/',
];
const IGNORE_COMMENT = 'lint-tailwind-ignore';

const PHYSICAL_ROOTS = {
  ml: 'ms',
  mr: 'me',
  pl: 'ps',
  pr: 'pe',
  left: 'inset-s',
  right: 'inset-e',
  'scroll-ml': 'scroll-ms',
  'scroll-mr': 'scroll-me',
  'scroll-pl': 'scroll-ps',
  'scroll-pr': 'scroll-pe',
  'border-l': 'border-s',
  'border-r': 'border-e',
  'rounded-l': 'rounded-s',
  'rounded-r': 'rounded-e',
  'rounded-tl': 'rounded-ss',
  'rounded-tr': 'rounded-se',
  'rounded-bl': 'rounded-es',
  'rounded-br': 'rounded-ee',
  'text-left': 'text-start',
  'text-right': 'text-end',
  'float-left': 'float-start',
  'float-right': 'float-end',
  'clear-left': 'clear-start',
  'clear-right': 'clear-end',
};

// Utilities that read the named --padding-*, --margin-*, --gap-* and
// --space-* steps defined in @craftcms/ui/tailwind.css.
const SPACING_ROOTS = new Set([
  ...['p', 'px', 'py', 'pt', 'pb', 'ps', 'pe', 'pl', 'pr', 'pbs', 'pbe'],
  ...['m', 'mx', 'my', 'mt', 'mb', 'ms', 'me', 'ml', 'mr', 'mbs', 'mbe'],
  ...['gap', 'gap-x', 'gap-y', 'space-x', 'space-y'],
]);

// --c-spacing is 0.25rem, the same as Tailwind's --spacing, so each step is an
// exact match for a numeric class.
const SPACING_STEPS = [
  ['xs', 0.5],
  ['sm', 1],
  ['md', 2],
  ['lg', 4],
  ['xl', 8],
  ['2xl', 16],
];

const designSystem = await __unstable__loadDesignSystem(
  readFileSync('resources/css/cp.css', 'utf8'),
  {base: resolve('resources/css')}
);
const scanner = new Scanner({});

const isValid = (candidate) =>
  designSystem.candidatesToCss([candidate])[0] != null;

/** Splits `md:hover:!-ml-2` into `md:hover:` and `!-ml-2`. */
function splitVariants(raw) {
  let depth = 0;
  for (let i = raw.length - 1; i >= 0; i--) {
    const char = raw[i];
    if (char === ']' || char === ')') depth++;
    else if (char === '[' || char === '(') depth--;
    else if (char === ':' && depth === 0) {
      return [raw.slice(0, i + 1), raw.slice(i + 1)];
    }
  }
  return ['', raw];
}

function withRoot(raw, root, newRoot) {
  const [variants, utility] = splitVariants(raw);
  const important = utility.startsWith('!') ? '!' : '';
  const rest = utility.slice(important.length);
  return rest.startsWith(root)
    ? variants + important + newRoot + rest.slice(root.length)
    : null;
}

function withValue(raw, value, newValue) {
  return raw.endsWith(`-${value}`)
    ? raw.slice(0, -value.length) + newValue
    : raw.endsWith(`-${value}!`)
      ? raw.slice(0, -value.length - 1) + newValue + '!'
      : null;
}

/** The named step that matches a numeric spacing value, or the two around it. */
function spacingSteps(value) {
  const number = Number(value);
  if (!Number.isFinite(number) || number === 0) return null;
  const exact = SPACING_STEPS.find(([, step]) => step === number);
  if (exact) return {exact: exact[0]};
  const below = SPACING_STEPS.filter(([, step]) => step < number).at(-1);
  const above = SPACING_STEPS.find(([, step]) => step > number);
  return {nearest: [below?.[0], above?.[0]].filter(Boolean)};
}

function check(raw) {
  const candidate = designSystem.parseCandidate(raw)[0];
  if (!candidate || !isValid(raw)) return null;

  const negative = candidate.root.startsWith('-') ? '-' : '';
  const root = candidate.root.slice(negative.length);
  const value =
    candidate.kind === 'functional' && candidate.value?.kind === 'named'
      ? candidate.value.value
      : null;

  let suggestion = raw;
  let physical = false;
  if (PHYSICAL_ROOTS[root]) {
    physical = true;
    suggestion = withRoot(
      raw,
      negative + root,
      negative + PHYSICAL_ROOTS[root]
    );
  }

  const steps =
    SPACING_ROOTS.has(root) && value !== null ? spacingSteps(value) : null;
  if (steps?.exact && suggestion) {
    const named = withValue(suggestion, value, steps.exact);
    if (named && isValid(named)) suggestion = named;
  }

  if (physical) {
    return {
      severity: 'error',
      message:
        suggestion && isValid(suggestion)
          ? `Use "${suggestion}" instead of "${raw}". Physical utilities break in RTL.`
          : `"${raw}" is physical and breaks in RTL. Use a logical utility.`,
    };
  }

  if (steps?.exact && suggestion !== raw) {
    return {
      severity: 'warning',
      message: `Use "${suggestion}" instead of "${raw}", to stay on the spacing scale.`,
    };
  }

  if (steps?.nearest) {
    const options = steps.nearest
      .map((step) => withValue(raw, value, step))
      .filter((option) => option && isValid(option))
      .map((option) => `"${option}"`);
    if (options.length) {
      return {
        severity: 'warning',
        message: `"${raw}" isn't on the spacing scale. Consider ${options.join(' or ')}.`,
      };
    }
  }

  return null;
}

function lintFile(file) {
  const content = staged
    ? execFileSync('git', ['show', `:${file}`], {encoding: 'utf8'})
    : readFileSync(file, 'utf8');
  const bytes = Buffer.from(content);
  const lines = content.split('\n');

  // Scanner positions are byte offsets.
  const lineStarts = [0];
  for (let i = 0; i < bytes.length; i++) {
    if (bytes[i] === 0x0a) lineStarts.push(i + 1);
  }

  const problems = [];
  const candidates = scanner.getCandidatesWithPositions({
    content,
    extension: extname(file).slice(1),
  });

  for (const {candidate, position} of candidates) {
    const result = check(candidate);
    if (!result) continue;

    let line = lineStarts.findLastIndex((start) => start <= position);
    if (
      lines[line]?.includes(IGNORE_COMMENT) ||
      lines[line - 1]?.includes(IGNORE_COMMENT)
    ) {
      continue;
    }

    const column =
      bytes.subarray(lineStarts[line], position).toString().length + 1;
    problems.push({file, line: line + 1, column, ...result});
  }

  return problems;
}

/** The lines added since `ref` (or `--cached`), per file. */
function addedLines(ref, files) {
  const diff = execFileSync(
    'git',
    ['diff', '--no-ext-diff', '--no-color', '-U0', ref, '--', ...files],
    {
      encoding: 'utf8',
      maxBuffer: 64 * 1024 * 1024,
    }
  );
  const added = new Map();
  let lines;
  for (const line of diff.split('\n')) {
    if (line.startsWith('+++ ')) {
      lines = new Set();
      added.set(line.replace(/^\+\+\+ b\//, ''), lines);
    } else if (line.startsWith('@@')) {
      const [, start, count = '1'] = line.match(/\+(\d+)(?:,(\d+))?/);
      for (let i = 0; i < Number(count); i++) lines.add(Number(start) + i);
    }
  }
  return added;
}

function filesToLint(args) {
  const files = args.length
    ? args
    : execFileSync(
        'git',
        staged
          ? ['diff', '--cached', '--name-only', '--diff-filter=ACMR']
          : ['ls-files', ...SOURCE_DIRS],
        {encoding: 'utf8'}
      )
        .split('\n')
        .filter(Boolean);

  return files
    .map((file) => file.replace(`${process.cwd()}/`, ''))
    .filter(
      (file) =>
        EXTENSIONS.has(extname(file)) &&
        SOURCE_DIRS.some((dir) => file.startsWith(`${dir}/`)) &&
        !IGNORED_PATHS.some((path) => file.startsWith(path))
    );
}

const args = process.argv.slice(2);
const quiet = args.includes('--quiet');
const staged = args.includes('--staged');
const diffIndex = args.indexOf('--diff');
const diffRef = staged
  ? '--cached'
  : diffIndex === -1
    ? null
    : args[diffIndex + 1];
const files = filesToLint(
  args.filter(
    (arg, i) =>
      !arg.startsWith('--') && (diffIndex === -1 || i !== diffIndex + 1)
  )
);
const changed = diffRef && files.length ? addedLines(diffRef, files) : null;

const problems = files
  .flatMap(lintFile)
  .filter(
    ({file, line, severity}) =>
      severity === 'error' ||
      (!quiet && (!changed || changed.get(file)?.has(line)))
  );
const inGitHubActions = process.env.GITHUB_ACTIONS === 'true';

for (const {file, line, column, severity, message} of problems) {
  console.log(
    inGitHubActions
      ? `::${severity} file=${file},line=${line},col=${column}::${message}`
      : `${file}:${line}:${column}  ${severity}  ${message}`
  );
}

const errors = problems.filter(({severity}) => severity === 'error').length;
const warnings = problems.length - errors;
if (problems.length) {
  console.log(`\n${errors} error(s), ${warnings} warning(s)`);
}
process.exitCode = errors ? 1 : 0;
