import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import {sharedParameters} from './helpers.js';

import '../../components/copy-attribute/copy-attribute.js';

const meta: Meta = {
  title: 'Tokens/Spacing',
  parameters: sharedParameters,
};

export default meta;
type Story = StoryObj;

const spacingTokens = [
  {name: '--c-spacing-xs', var: 'var(--c-spacing-xs)', px: '2px'},
  {name: '--c-spacing-sm', var: 'var(--c-spacing-sm)', px: '4px'},
  {name: '--c-spacing-md', var: 'var(--c-spacing-md)', px: '8px'},
  {name: '--c-spacing-lg', var: 'var(--c-spacing-lg)', px: '16px'},
  {name: '--c-spacing-xl', var: 'var(--c-spacing-xl)', px: '32px'},
  {name: '--c-spacing-2xl', var: 'var(--c-spacing-2xl)', px: '64px'},
];

export const Default: Story = {
  render: () => html`
    <div class="stage">
      <table class="cp-table cp-table--padded">
        <thead>
          <tr>
            <th>Preview</th>
            <th>Variable</th>
            <th>Value</th>
          </tr>
        </thead>
        <tbody>
          ${spacingTokens.map(
            (s) => html`
              <tr>
                <td>
                  <div
                    style="background-color:var(--c-color-accent-fill-loud);height:${s.var};width:${s.var};"
                  ></div>
                </td>
                <td>
                  <craft-copy-attribute
                    .value="var(${s.name})"
                  ></craft-copy-attribute>
                </td>
                <td><code>${s.px}</code></td>
              </tr>
            `
          )}
        </tbody>
      </table>
    </div>
  `,
};
