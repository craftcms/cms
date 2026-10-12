import {
  existsSync,
  globSync,
  readFileSync,
  readdirSync,
  statSync,
} from 'node:fs';
import {join} from 'node:path';
import {describe, expect, it} from 'vite-plus/test';

/**
 * Every component carries a `<name>.a11y.md` beside it: the requirements
 * checklist that covers what axe can't, and the interaction pattern agents
 * read to pick the right component. A component without one has no spec to
 * test its accessibility against, and nothing says so.
 */

const COMPONENTS = join(import.meta.dirname, '.');

const components = readdirSync(COMPONENTS, {withFileTypes: true})
  .filter(
    (entry) =>
      entry.isDirectory() &&
      !entry.name.startsWith('.') &&
      entry.name !== 'node_modules'
  )
  .map((entry) => ({
    component: entry.name,
    doc: join(COMPONENTS, entry.name, `${entry.name}.a11y.md`),
  }));

describe('components document their accessibility requirements', () => {
  /** A guard that silently matches nothing would be worse than none at all. */
  it('finds the components', () => {
    expect(components.length).toBeGreaterThan(0);
  });

  it.each(components)('$component', ({doc}) => {
    expect(existsSync(doc), `${doc} does not exist`).toBe(true);
    expect(readFileSync(doc, 'utf8')).toMatch(/^## Requirements$/m);
  });
});

/**
 * A checklist item is only worth checking off if something in this package
 * verifies it. Guidance that depends on how the component is used lives in its
 * `.mdx` Accessibility section instead, where it can be read but not tested.
 */

const SRC = join(import.meta.dirname, '..');
const RULE = 'See "Document a11y requirements" in .ai/rules/craftcms-ui.md.';

const documented = components
  .filter(({doc}) => existsSync(doc))
  .map(({component, doc}) => ({
    component,
    dir: join(COMPONENTS, component),
    source: readFileSync(doc, 'utf8'),
  }));

/** Top-level checklist items, with their wrapped and nested lines folded in. */
function checklist(source: string): Array<{checked: boolean; text: string}> {
  const items: Array<{checked: boolean; text: string}> = [];
  let current: {checked: boolean; text: string} | null = null;

  for (const line of source.split('\n')) {
    const start = /^- \[([ x])\] /.exec(line);

    if (start) {
      current = {checked: start[1] === 'x', text: line};
      items.push(current);
    } else if (current && /^\s+\S/.test(line)) {
      current.text += `\n${line}`;
    } else {
      current = null;
    }
  }

  return items;
}

/** Test and story files anywhere in the package, by path relative to `src`. */
const testFiles = globSync('**/*.{test,stories}.ts', {cwd: SRC}).filter(
  (file) => !file.includes('node_modules') && statSync(join(SRC, file)).isFile()
);

const testSources = testFiles.map((file) => ({
  file,
  source: readFileSync(join(SRC, file), 'utf8'),
}));

const CITED_FILE = /`([^`\s]+(?:\.test\.ts|\.stories\.ts|Test\.php))`/g;
const CITED_STORY = /`([A-Z][A-Za-z0-9]*)`/g;

function resolveInPackage(cited: string) {
  return testSources.find(
    ({file}) => file === cited || file.endsWith(`/${cited}`)
  );
}

/**
 * Stories exported from the component's own stories file, each mapped to
 * whether it defines a `play()` function. Only a story with one can verify a
 * requirement; a story without one just renders.
 */
function storyExports(dir: string): Map<string, boolean> {
  return new Map(
    globSync('*.stories.ts', {cwd: dir}).flatMap((file) =>
      readFileSync(join(dir, file), 'utf8')
        .split(/^(?=export const )/m)
        .flatMap((chunk): Array<[string, boolean]> => {
          const name = /^export const ([A-Za-z0-9]+)\s*:/.exec(chunk)?.[1];

          return name ? [[name, /\bplay\s*[:(]/.test(chunk)]] : [];
        })
    )
  );
}

function matchAll(source: string, pattern: RegExp): string[] {
  return [...source.matchAll(pattern)].map((match) => match[1]!);
}

describe('a11y docs check off only what this package verifies', () => {
  it('finds checked items to verify', () => {
    expect(
      documented
        .flatMap(({source}) => checklist(source))
        .filter(({checked}) => checked).length
    ).toBeGreaterThan(0);
  });

  it.each(documented)('$component', ({dir, source}) => {
    const stories = storyExports(dir);
    const problems: string[] = [];

    for (const {text} of checklist(source).filter(({checked}) => checked)) {
      const summary = text.split('\n')[0]!.slice(0, 80);
      const files = matchAll(text, CITED_FILE);
      const resolvedFiles = files.flatMap((file) => {
        const resolved = resolveInPackage(file);

        return resolved ? [{file, source: resolved.source}] : [];
      });
      const outside = files.filter((file) => !resolveInPackage(file));
      const tests = resolvedFiles.filter(
        ({file}) => !file.endsWith('.stories.ts')
      );
      const citedSources = resolvedFiles.map(({source}) => source);
      const citesStory =
        /\bstor(?:y|ies)\b/.test(text) &&
        matchAll(text, CITED_STORY).some((name) => stories.get(name) === true);
      const verifiedBy = text.split('Verified by').slice(1).join('Verified by');
      const missingNames = [
        ...matchAll(verifiedBy, /`([^`]+)`/g),
        ...matchAll(
          verifiedBy,
          /\.test\.ts`'s ((?:"[^"\n]+"(?:,? and |, )?)+)/g
        ).flatMap((run) => matchAll(run, /"([^"\n]+)"/g)),
      ].filter(
        (name) =>
          name.includes(' ') &&
          !name.includes('…') &&
          !citedSources.some((test) => test.includes(name))
      );

      if (outside.length) {
        problems.push(
          `${summary} cites ${outside.join(', ')}, outside this package`
        );
      }

      if (!tests.length && !citesStory) {
        problems.push(
          `${summary} cites no test, or story with a play() assertion, in this package`
        );
      }

      const citedStories = matchAll(
        text,
        /`([A-Z][A-Za-z0-9]*)` stor(?:y|ies)\b/g
      );
      const missingStories = citedStories.filter((name) => !stories.has(name));

      if (missingStories.length) {
        problems.push(
          `${summary} cites the ${missingStories.join(', ')} story, which doesn't exist`
        );
      }

      if (missingNames.length) {
        problems.push(
          `${summary} cites ${missingNames.join(', ')}, which no test or story contains`
        );
      }
    }

    expect(problems, RULE).toEqual([]);
  });
});

describe('a11y docs leave usage guidance to the .mdx page', () => {
  it.each(documented)('$component', ({source}) => {
    expect(
      matchAll(source.replace(/`[^`\n]*`/g, '``'), /^(.*\bconsumers?\b.*)$/gim),
      `Usage guidance belongs in the component's .mdx Accessibility section. ${RULE}`
    ).toEqual([]);
  });

  it.each(documented)(
    '$component links its usage notes to an .mdx page',
    ({dir, source}) => {
      const section =
        /^## Depends on usage\n([\s\S]*?)(?=^## |(?![\s\S]))/m.exec(
          source
        )?.[1];

      if (!section) {
        return;
      }

      const bullets = section
        .split('\n')
        .filter((line) => line.startsWith('- '));
      const problems = bullets.filter((line) => {
        const page = /\(\.\/([A-Za-z0-9-]+\.mdx)(?:#[a-z0-9-]+)?\)/.exec(
          line
        )?.[1];

        return (
          /^- \[[ x]\]/.test(line) || !page || !existsSync(join(dir, page))
        );
      });

      expect(bullets.length, RULE).toBeGreaterThan(0);
      expect(
        problems,
        `Each usage note is a plain bullet linking an .mdx page in this folder. ${RULE}`
      ).toEqual([]);
    }
  );
});
