import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import {sharedParameters} from './helpers.js';

import '../../components/copy-attribute/copy-attribute.js';

const meta: Meta = {
  title: 'Tokens/Text',
  parameters: {
    ...sharedParameters,
    a11y: {test: 'todo'},
  },
};

export default meta;
type Story = StoryObj;

const textTokens = [
  {
    name: '--c-text-default',
    var: 'var(--c-text-default)',
    sample: 'Default body text',
  },
  {
    name: '--c-text-quiet',
    var: 'var(--c-text-quiet)',
    sample: 'Muted / secondary text',
  },
  {name: '--c-text-link', var: 'var(--c-text-link)', sample: 'Link text'},
  {
    name: '--c-text-white',
    var: 'var(--c-text-white)',
    sample: 'White text (on dark bg)',
  },
];

export const Default: Story = {
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
          ${textTokens.map(
            (t) => html`
              <tr>
                <td>
                  <div style="color: ${t.var}">${t.sample}</div>
                </td>

                <td>
                  <craft-copy-attribute
                    .value="${`var(${t.name})`}"
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
