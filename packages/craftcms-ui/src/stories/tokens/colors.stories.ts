import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import {groups, sharedParameters, swatchStyle} from './helpers.js';

const meta: Meta = {
  title: 'Tokens/Semantic Colors',
  parameters: sharedParameters,
};

export default meta;
type Story = StoryObj;

export const Default: Story = {
  render: () => html`
    <div class="stage">
      <table class="cp-table cp-table--padded">
        <thead>
          <tr>
            <th>Group</th>
            <th>fill-loud</th>
            <th>fill-normal</th>
            <th>fill-quiet</th>
            <th>border-loud</th>
            <th>border-normal</th>
            <th>border-quiet</th>
            <th>on-loud</th>
            <th>on-normal</th>
            <th>on-quiet</th>
          </tr>
        </thead>
        <tbody>
          ${groups.map(
            (g) => html`
              <tr>
                <td>
                  <strong>${g.group}</strong>
                </td>
                ${g.fills.map(
                  (f) => html`
                    <td>
                      <div class="swatch" style="${swatchStyle(f)}"></div>
                    </td>
                  `
                )}
                ${g.borders.map(
                  (b) => html`
                    <td>
                      <div class="swatch" style="border:3px solid ${b};"></div>
                    </td>
                  `
                )}
                ${g.fills.map(
                  (f, i) => html`
                    <td>
                      <div
                        class="swatch"
                        style="background-color:${f};color:${g.ons[i]};"
                      >
                        Aa
                      </div>
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
