import {existsSync, readFileSync, readdirSync} from 'node:fs';
import {join} from 'node:path';
import {describe, expect, it} from 'vite-plus/test';

/**
 * Every component carries a `<name>.a11y.md` beside it: the requirements
 * checklist that covers what axe can't, and the interaction pattern agents
 * read to pick the right component. A component without one has no spec to
 * test its accessibility against, and nothing says so.
 */

const COMPONENTS = join(import.meta.dirname, '.');

/**
 * Components still waiting on their first `.a11y.md`. This list only shrinks:
 * a component listed here that has gained a doc fails until it's removed.
 */
const PENDING = new Set([
  'action-item',
  'action-menu',
  'avatar',
  'badge-indicator',
  'breadcrumbs',
  'button-group',
  'callout',
  'card',
  'checkbox',
  'checkbox-group',
  'checkbox-indeterminate',
  'combobox',
  'copy-attribute',
  'copy-button',
  'dialog',
  'disclosure',
  'element-selector-modal',
  'empty',
  'field',
  'field-group',
  'icon',
  'info-icon',
  'input',
  'input-color',
  'input-copy',
  'input-date',
  'input-file',
  'input-handle',
  'input-money',
  'input-password',
  'input-time',
  'missing-component',
  'nav-list',
  'option',
  'pane',
  'permission-tree',
  'popover',
  'progress',
  'progress-bar',
  'radio',
  'radio-group',
  'reorder-button',
  'select',
  'select-color',
  'select-rich',
  'shortcut',
  'slide-picker',
  'slide-rule',
  'spinner',
  'status',
  'switch',
  'switch-button',
  'tab',
  'text-expander',
  'textarea',
  'thumbnail-loader',
  'timeline-item',
  'truncate',
  'visually-hidden',
]);

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
    expect(components.length).toBeGreaterThan(PENDING.size);
  });

  it.each(components.filter(({component}) => !PENDING.has(component)))(
    '$component',
    ({doc}) => {
      expect(existsSync(doc), `${doc} does not exist`).toBe(true);
      expect(readFileSync(doc, 'utf8')).toMatch(/^## Requirements$/m);
    }
  );

  it('lists only components still missing a doc as pending', () => {
    const documented = components
      .filter(({component, doc}) => PENDING.has(component) && existsSync(doc))
      .map(({component}) => component);

    expect(documented).toEqual([]);
  });

  it('lists only components that exist as pending', () => {
    const names = new Set(components.map(({component}) => component));

    expect([...PENDING].filter((name) => !names.has(name))).toEqual([]);
  });
});
