import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {expect, waitFor} from 'storybook/test';
import {html} from 'lit';
import {ref} from 'lit/directives/ref.js';
import OverType from 'overtype';

import type {
  TextExpanderOption,
  TextExpanderTriggers,
} from './text-expander.js';
import './text-expander.js';

const people = [
  {label: 'Ada Lovelace', value: '@ada', keywords: ['lovelace']},
  {label: 'Grace Hopper', value: '@grace', keywords: ['hopper']},
  {label: 'Linus Torvalds', value: '@linus', keywords: ['torvalds']},
];

const variables = [
  {label: 'Base URL', value: '#BASE_URL'},
  {label: 'Environment', value: '#ENVIRONMENT'},
  {label: 'Site name', value: '#SITE_NAME'},
];

const triggers: TextExpanderTriggers = [
  {trigger: '@', boundary: 'whitespace', label: 'People', options: people},
  {
    trigger: '#',
    boundary: 'whitespace',
    label: 'Environment',
    options: variables,
  },
];

const meta = {
  title: 'Components/Text Expander',
  component: 'craft-text-expander',
  parameters: {layout: 'centered'},
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const TextInput: Story = {
  render: () => html`
    <label>
      Mention someone with @
      <input id="mention-input" type="text" style="width: 24rem" />
    </label>
    <craft-text-expander
      for="mention-input"
      .triggers=${[
        {
          trigger: '@',
          boundary: 'whitespace',
          label: 'People',
          options: people,
        },
      ]}
    ></craft-text-expander>
  `,
  play: async ({canvas, canvasElement, userEvent}) => {
    const input = canvas.getByRole('textbox');
    const expander = canvasElement.querySelector('craft-text-expander')!;
    const popover = expander.shadowRoot!.querySelector('craft-popover')!;
    const popup = popover.shadowRoot!.querySelector('[part="popup"]')!;

    await expect(getComputedStyle(popup).minWidth).toBe('0px');

    await userEvent.type(input, '@a');
    await waitFor(() =>
      expect(
        canvas.getByRole('option', {name: 'Ada Lovelace'})
      ).toHaveAttribute('aria-selected', 'true')
    );
    await userEvent.clear(input);
    await userEvent.type(input, '@z');
    await waitFor(() =>
      expect(popover.shadowRoot!.querySelector('dialog')?.style.display).toBe(
        'none'
      )
    );
    await userEvent.clear(input);
    await userEvent.type(input, '@a');
    await waitFor(() =>
      expect(
        canvas.getByRole('option', {name: 'Ada Lovelace'})
      ).toHaveAttribute('aria-selected', 'true')
    );
    await userEvent.keyboard('{Enter}');

    await expect(input).toHaveValue('@ada');
  },
};

export const TextareaWithMultipleTriggers: Story = {
  render: () => html`
    <label>
      Use @ for people or # for environment variables
      <textarea
        id="multi-trigger-input"
        rows="6"
        style="width: 32rem"
      ></textarea>
    </label>
    <craft-text-expander
      for="multi-trigger-input"
      .triggers=${triggers}
    ></craft-text-expander>
  `,
};

export const RichOptions: Story = {
  render: () => html`
    <label>
      Search for a team member with @
      <input id="async-input" type="text" style="width: 24rem" />
    </label>
    <craft-text-expander
      for="async-input"
      .triggers=${[
        {
          trigger: '@',
          boundary: 'whitespace',
          options: people,
          renderOption(option: Readonly<TextExpanderOption>) {
            const row = document.createElement('span');
            const status = document.createElement('span');
            row.style.cssText = 'display:flex;align-items:center;gap:0.5rem';
            status.ariaHidden = 'true';
            status.style.color = '#27ab83';
            status.textContent = '●';
            row.append(status, option.label);
            return row;
          },
        },
      ]}
    ></craft-text-expander>
  `,
};

export const MarkdownInput: Story = {
  render: () => html`
    <div style="width: 40rem">
      <div
        class="markdown-input-story"
        ${ref((element) => {
          if (
            !(element instanceof HTMLElement) ||
            element.querySelector('textarea')
          ) {
            return;
          }

          new OverType(element, {
            autoResize: true,
            textareaProps: {id: 'markdown-input'},
            value: 'Type @ to mention someone.',
          });
        })}
      ></div>
      <craft-text-expander
        for="markdown-input"
        .triggers=${[{trigger: '@', boundary: 'whitespace', options: people}]}
      ></craft-text-expander>
    </div>
  `,
};
