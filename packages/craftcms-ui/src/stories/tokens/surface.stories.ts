import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import {sharedParameters} from './helpers.js';

const meta: Meta = {
  title: 'Tokens/Surface',
  parameters: sharedParameters,
};

export default meta;
type Story = StoryObj;

const surfaces = [
  {name: '--c-surface-default', var: 'var(--c-surface-default)'},
  {name: '--c-surface-raised', var: 'var(--c-surface-raised)'},
  {name: '--c-surface-sunken', var: 'var(--c-surface-sunken)'},
  {name: '--c-surface-overlay', var: 'var(--c-surface-overlay)'},
  {name: '--c-surface-form', var: 'var(--c-surface-form)'},
];

export const Surfaces: Story = {
  render: () => html`
    <div class="stage">
      <table class="cp-table cp-table--padded">
        <thead>
          <tr>
            <th>Preview</th>
            <th>Variable</th>
          </tr>
        </thead>
        <tbody>
          ${surfaces.map(
            (s) => html`
              <tr>
                <td class="cell">
                  <div class="swatch" style="background-color:${s.var}"></div>
                </td>
                <td>
                  <craft-copy-attribute
                    .value="${`var(${s.name})`}"
                  ></craft-copy-attribute>
                </td>
              </tr>
            `
          )}
        </tbody>
      </table>
    </div>
  `,
};

const shadowTokens = [
  {name: '--c-shadow-2xs', var: 'var(--c-shadow-2xs)'},
  {name: '--c-shadow-xs', var: 'var(--c-shadow-xs)'},
  {name: '--c-shadow-sm', var: 'var(--c-shadow-sm)'},
  {name: '--c-shadow-md', var: 'var(--c-shadow-md)'},
  {name: '--c-shadow-lg', var: 'var(--c-shadow-lg)'},
  {name: '--c-shadow-xl', var: 'var(--c-shadow-xl)'},
];

export const Shadows: Story = {
  render: () => html`
    <div class="stage">
      <table class="cp-table cp-table--padded">
        <thead>
          <tr>
            <th>Preview</th>
            <th>Variable</th>
          </tr>
        </thead>
        <tbody>
          ${shadowTokens.map(
            (s) => html`
              <tr>
                <td class="cell">
                  <div class="swatch" style="box-shadow: ${s.var}"></div>
                </td>
                <td>
                  <craft-copy-attribute
                    .value="${`var(${s.name})`}"
                  ></craft-copy-attribute>
                </td>
              </tr>
            `
          )}
        </tbody>
      </table>
    </div>
  `,
};
