import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html, nothing} from 'lit';
import {sharedParameters} from './helpers.js';

import '../../components/callout/callout.js';
import '../../components/button/button.js';
import '../../components/indicator/indicator.js';

import {appearances} from '@src/constants/appearances';
import {variants} from '@src/constants/variants';
import {ButtonVariant} from '@src/components/button/button';

const buttonVariants = Object.values(ButtonVariant);

const meta: Meta = {
  title: 'Tokens/Variants & Appearances',
  parameters: sharedParameters,
};

export default meta;
type Story = StoryObj;

/**
 * A matrix of all variant x appearance combinations using `<craft-callout>`.
 * Columns = appearances, rows = variants.
 */
export const CalloutMatrix: Story = {
  name: 'Callout Matrix',
  render: () => html`
    <div class="stage">
      <table class="cp-table cp-table--padded">
        <thead>
          <tr>
            <th>variant</th>
            ${appearances.map((a) => html`<th>${a}</th>`)}
          </tr>
        </thead>
        <tbody>
          ${variants.map(
            (variant) => html`
              <tr>
                <td><strong>${variant}</strong></td>
                ${appearances.map(
                  (appearance) => html`
                    <td>
                      <craft-callout
                        variant="${variant}"
                        appearance="${appearance}"
                        rounded="all"
                      >
                        ${variant} / ${appearance}
                      </craft-callout>
                    </td>
                  `
                )}
              </tr>
            `
          )}
        </tbody>
      </table>
    </div>
  `,
};

/**
 * Buttons use a single `variant` axis (primary, danger, solid, fill, outline,
 * dashed, plain, link, none — plus inherit).
 */
export const ButtonMatrix: Story = {
  name: 'Button Matrix',
  render: () => html`
    <div class="stage">
      <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        ${buttonVariants.map(
          (variant) => html`
            <craft-button variant="${variant}">${variant}</craft-button>
          `
        )}
      </div>
    </div>
  `,
};

/**
 * Reference table showing how variant attributes map generic
 * `--c-color-*` custom properties to semantic group tokens.
 */
export const TokenMapping: Story = {
  name: 'Token Mapping',
  render: () => {
    const genericTokens = [
      'fill-loud',
      'fill-normal',
      'fill-quiet',
      'border-loud',
      'border-normal',
      'border-quiet',
      'on-loud',
      'on-normal',
      'on-quiet',
    ];

    const variantGroupMap: Record<string, string> = {
      default: 'neutral',
      success: 'success',
      warning: 'warning',
      danger: 'danger',
      info: 'info',
    };

    return html`
      <table class="cp-table cp-table--padded">
        <thead>
          <tr>
            <th>Generic token</th>
            ${variants.map((v) => html`<th>${v}</th>`)}
          </tr>
        </thead>
        <tbody>
          ${genericTokens.map(
            (token) => html`
              <tr>
                <td>
                  <code style="font-size: 0.85em">--c-color-${token}</code>
                </td>
                ${variants.map((v: string) => {
                  const group = variantGroupMap[v];
                  const resolved = `--c-color-${group}-${token}`;
                  const isFill = token.startsWith('fill');
                  const isBorder = token.startsWith('border');
                  return html`
                    <td>
                      <div
                        style="display: flex; align-items: center; gap: 0.5rem;"
                      >
                        ${isFill
                          ? html`<div
                              class="swatch"
                              style="background-color: var(${resolved}); width: 24px;"
                            ></div>`
                          : isBorder
                            ? html`<div
                                class="swatch"
                                style="border: 3px solid var(${resolved}); width: 24px;"
                              ></div>`
                            : html`<div
                                class="swatch"
                                style="background-color: var(--c-color-${group}-fill-${token.replace(
                                  'on-',
                                  ''
                                )}); color: var(${resolved}); width: 24px;"
                              >
                                Aa
                              </div>`}
                        <code style="font-size: 0.75em">${resolved}</code>
                      </div>
                    </td>
                  `;
                })}
              </tr>
            `
          )}
        </tbody>
      </table>
    `;
  },
};

/**
 * Side-by-side comparison showing how appearances control visual
 * intensity independently of variant.
 */
export const AppearanceScale: Story = {
  name: 'Appearance Scale',
  render: () => html`
    <div class="stage">
      <div style="display: grid; gap: 2rem;">
        ${variants.map(
          (variant) => html`
            <div>
              <p style="margin: 0 0 0.5rem; font-weight: 600;">${variant}</p>
              <div
                style="display: flex; gap: 0.5rem; align-items: start; flex-wrap: wrap;"
              >
                ${appearances.map(
                  (appearance) => html`
                    <craft-callout
                      variant="${variant}"
                      appearance="${appearance}"
                      rounded="all"
                      style="min-width: 140px;"
                    >
                      ${appearance}
                    </craft-callout>
                  `
                )}
              </div>
            </div>
          `
        )}
      </div>
    </div>
  `,
};
