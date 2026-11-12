import {readFileSync} from 'node:fs';
import {join} from 'node:path';
import {describe, expect, it} from 'vite-plus/test';
import {themeEntries} from './tailwind-theme';

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
        --text-color-quiet: var(--c-text-quiet);
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
        namespace: 'text-color',
        name: 'quiet',
        value: 'var(--c-text-quiet)',
        token: '--c-text-quiet',
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

  it('maps every layer token, so the Layers story lists them all', () => {
    const tokens = readFileSync(
      join(import.meta.dirname, '../../styles/shared/tokens.css'),
      'utf8'
    );
    const layerTokens = [...tokens.matchAll(/(--c-layer-[\w-]+)\s*:/g)].map(
      ([, token]) => token
    );
    const mappedTokens = themeEntries(
      readFileSync(join(import.meta.dirname, '../../../tailwind.css'), 'utf8')
    )
      .filter((entry) => entry.namespace === 'z-index')
      .map((entry) => entry.token);

    expect(layerTokens).toContain('--c-layer-skip-link');
    expect(mappedTokens).toEqual(expect.arrayContaining(layerTokens));
  });
});
