import {readFileSync} from 'node:fs';
import {join} from 'node:path';
import {describe, expect, it} from 'vite-plus/test';
import {themeEntries, type ThemeNamespace} from './tailwind-theme';

describe('themeEntries', () => {
  it('reads each namespace from a theme block', () => {
    const entries = themeEntries(`
      @theme inline {
        /* comments are skipped */
        --color-surface-default: var(--c-surface-default);
        --background-color-raised: var(--c-surface-raised);
        --border-color-quiet: var(
          --c-color-border-quiet,
          var(--c-color-neutral-border-quiet)
        );
        --padding-sm: var(--c-spacing-sm);
        --z-index-popover: var(--c-layer-popover);
        --unrelated-thing: 1px;
      }
    `);

    expect(entries).toEqual([
      {
        namespace: 'color',
        name: 'surface-default',
        value: 'var(--c-surface-default)',
        token: '--c-surface-default',
      },
      {
        namespace: 'background-color',
        name: 'raised',
        value: 'var(--c-surface-raised)',
        token: '--c-surface-raised',
      },
      {
        namespace: 'border-color',
        name: 'quiet',
        value:
          'var(--c-color-border-quiet, var(--c-color-neutral-border-quiet))',
        token: '--c-color-border-quiet',
      },
      {
        namespace: 'padding',
        name: 'sm',
        value: 'var(--c-spacing-sm)',
        token: '--c-spacing-sm',
      },
      {
        namespace: 'z-index',
        name: 'popover',
        value: 'var(--c-layer-popover)',
        token: '--c-layer-popover',
      },
    ]);
  });

  it('finds the mappings in the real tailwind.css', () => {
    const theme = themeEntries(
      readFileSync(join(import.meta.dirname, '../../../tailwind.css'), 'utf8')
    );
    const entriesIn = (namespace: ThemeNamespace) =>
      theme.filter((entry) => entry.namespace === namespace);

    expect(entriesIn('border-color').map((entry) => entry.name)).toEqual([
      'quiet',
      'normal',
      'loud',
    ]);
    expect(entriesIn('padding').map((entry) => entry.token)).toContain(
      '--c-spacing-md'
    );
    expect(entriesIn('z-index').map((entry) => entry.name)).toContain(
      'popover'
    );
    expect(entriesIn('background-color').map((entry) => entry.name)).toEqual([
      'default',
      'raised',
      'sunken',
      'overlay',
      'form',
      'header',
    ]);
    expect(entriesIn('color').length).toBeGreaterThan(0);
  });
});
