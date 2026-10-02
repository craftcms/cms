import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import {sharedParameters} from './helpers.js';
import {entriesIn, type ThemeEntry} from './tailwind-theme.js';

import '../../components/copy-attribute/copy-attribute.js';

const meta: Meta = {
  title: 'Tokens/Tailwind Utilities',
  parameters: sharedParameters,
};

export default meta;
type Story = StoryObj;

const tokenCell = (entry: ThemeEntry) =>
  entry.token
    ? html`<craft-copy-attribute
        .value="${entry.token}"
      ></craft-copy-attribute>`
    : html`<code>${entry.value}</code>`;

const classCell = (className: string) =>
  html`<craft-copy-attribute .value="${className}"></craft-copy-attribute>`;

const classList = (classNames: string[]) =>
  html`<code>${classNames.join(' ')}</code>`;

/**
 * Surfaces: `--background-color-*` feeds only `bg-*`, so these give
 * `bg-default`, `bg-raised`, … without a matching `text-default`.
 */
export const Surfaces: Story = {
  render: () => html`
    <div class="stage">
      <table class="cp-table cp-table--padded">
        <thead>
          <tr>
            <th>Preview</th>
            <th>Class</th>
            <th>Token</th>
          </tr>
        </thead>
        <tbody>
          ${entriesIn('background-color').map(
            (entry) => html`
              <tr>
                <td>
                  <div
                    class="swatch swatch--border"
                    style="background-color:${entry.value}"
                  ></div>
                </td>
                <td>${classCell(`bg-${entry.name}`)}</td>
                <td>${tokenCell(entry)}</td>
              </tr>
            `
          )}
        </tbody>
      </table>
    </div>
  `,
};

/**
 * Colors: `--color-*` feeds every color utility, so each name works with
 * `bg-`, `text-`, `border-` and the rest.
 */
export const Colors: Story = {
  render: () => html`
    <div class="stage">
      <table class="cp-table cp-table--padded">
        <thead>
          <tr>
            <th>Preview</th>
            <th>Background</th>
            <th>Also</th>
            <th>Token</th>
          </tr>
        </thead>
        <tbody>
          ${entriesIn('color').map(
            (entry) => html`
              <tr>
                <td>
                  <div
                    class="swatch swatch--border"
                    style="background-color:${entry.value}"
                  ></div>
                </td>
                <td>${classCell(`bg-${entry.name}`)}</td>
                <td>
                  ${classList([`text-${entry.name}`, `border-${entry.name}`])}
                </td>
                <td>${tokenCell(entry)}</td>
              </tr>
            `
          )}
        </tbody>
      </table>
    </div>
  `,
};

/**
 * Border colors: `--border-color-*` feeds only border and divide utilities,
 * so these don't also generate `bg-quiet` or `text-quiet`. They follow the
 * surrounding `data-color`.
 */
export const BorderColors: Story = {
  name: 'Border Colors',
  render: () => html`
    <div class="stage">
      <table class="cp-table cp-table--padded">
        <thead>
          <tr>
            <th>Preview</th>
            <th>Class</th>
            <th>Also</th>
            <th>Token</th>
          </tr>
        </thead>
        <tbody>
          ${entriesIn('border-color').map(
            (entry) => html`
              <tr>
                <td>
                  <div
                    class="swatch"
                    style="border:2px solid ${entry.value}"
                  ></div>
                </td>
                <td>${classCell(`border-${entry.name}`)}</td>
                <td>
                  ${classList([
                    ...['t', 'r', 'b', 'l', 'x', 'y', 's', 'e'].map(
                      (side) => `border-${side}-${entry.name}`
                    ),
                    `divide-${entry.name}`,
                  ])}
                </td>
                <td>${tokenCell(entry)}</td>
              </tr>
            `
          )}
        </tbody>
      </table>
    </div>
  `,
};

/**
 * Spacing: the named `--c-spacing-*` steps, mapped separately for padding,
 * margin, gap and space. Not `--spacing-*`, which `w-*`, `max-w-*` and
 * `min-w-*` read before their container sizes.
 */
export const Spacing: Story = {
  render: () => {
    const steps = entriesIn('padding');
    const has = (namespace: 'margin' | 'gap' | 'space', name: string) =>
      entriesIn(namespace).some((entry) => entry.name === name);

    return html`
      <div class="stage">
        <table class="cp-table cp-table--padded">
          <thead>
            <tr>
              <th>Preview</th>
              <th>Token</th>
              <th>Padding</th>
              <th>Margin</th>
              <th>Gap &amp; space</th>
            </tr>
          </thead>
          <tbody>
            ${steps.map(
              (entry) => html`
                <tr>
                  <td>
                    <div
                      style="background-color:var(--c-color-accent-fill-loud);height:${entry.value};width:${entry.value};"
                    ></div>
                  </td>
                  <td>${tokenCell(entry)}</td>
                  <td>
                    ${classList(
                      ['p', 'px', 'py', 'pt', 'pr', 'pb', 'pl', 'ps', 'pe'].map(
                        (prefix) => `${prefix}-${entry.name}`
                      )
                    )}
                  </td>
                  <td>
                    ${has('margin', entry.name)
                      ? classList(
                          ['m', 'mx', 'my', 'mt', 'mr', 'mb', 'ml', 'ms', 'me']
                            .map((prefix) => `${prefix}-${entry.name}`)
                            .concat(`-mt-${entry.name}`)
                        )
                      : '—'}
                  </td>
                  <td>
                    ${classList([
                      ...(has('gap', entry.name)
                        ? ['gap', 'gap-x', 'gap-y'].map(
                            (prefix) => `${prefix}-${entry.name}`
                          )
                        : []),
                      ...(has('space', entry.name)
                        ? ['space-x', 'space-y'].map(
                            (prefix) => `${prefix}-${entry.name}`
                          )
                        : []),
                    ])}
                  </td>
                </tr>
              `
            )}
          </tbody>
        </table>
      </div>
    `;
  },
};

/** The z-index a layer token resolves to, measured on a positioned probe. */
function resolvedLayer(value: string): string {
  const probe = document.createElement('div');
  probe.style.position = 'relative';
  probe.style.zIndex = value;
  document.body.append(probe);
  const resolved = getComputedStyle(probe).zIndex;
  probe.remove();

  return resolved;
}

/**
 * Layers: `--z-index-*` gives `z-overlay`, `z-popover` and friends, ordered
 * here from lowest to highest. Named layers have no negative form.
 */
export const Layers: Story = {
  render: () => {
    const layers = entriesIn('z-index')
      .map((entry) => ({entry, resolved: resolvedLayer(entry.value)}))
      .sort((a, b) => Number(a.resolved) - Number(b.resolved));

    return html`
      <div class="stage">
        <table class="cp-table cp-table--padded">
          <thead>
            <tr>
              <th>Class</th>
              <th>Token</th>
              <th>Value</th>
            </tr>
          </thead>
          <tbody>
            ${layers.map(
              ({entry, resolved}) => html`
                <tr>
                  <td>${classCell(`z-${entry.name}`)}</td>
                  <td>${tokenCell(entry)}</td>
                  <td><code>${resolved}</code></td>
                </tr>
              `
            )}
          </tbody>
        </table>
      </div>
    `;
  },
};
