import {beforeEach, expect, it, vi} from 'vite-plus/test';
import type CraftField from './field.js';
import type CraftInput from '../input/input.js';
import './field.js';
import '../input/input.js';
import '../../styles/cp.css';

beforeEach(() => {
  document.body.innerHTML = '';
});

/** Offsets of the help text, input and tip text within a field's shadow root. */
async function layout(host: CraftField | CraftInput) {
  await host.updateComplete;
  const shadow = host.shadowRoot!;
  const help = shadow.querySelector('.form-field__help-text')!;
  const input = shadow.querySelector('.input-group__container')!;
  const notice = shadow.querySelector<HTMLElement>('.field-notice')!;
  await (notice as HTMLElement & {updateComplete: Promise<unknown>})
    .updateComplete;
  const icon = notice.shadowRoot!.querySelector('.callout__icon')!;
  const text = notice.shadowRoot!.querySelector('.callout__description')!;

  return {
    helpToInput:
      input.getBoundingClientRect().top - help.getBoundingClientRect().bottom,
    iconInset:
      icon.getBoundingClientRect().left - notice.getBoundingClientRect().left,
    iconToText:
      text.getBoundingClientRect().left - icon.getBoundingClientRect().right,
  };
}

it('lays out a standalone input like a wrapped field', async () => {
  document.body.innerHTML = `
    <craft-field label="Summary" help-text="Shown in listings.">
      <input slot="input">
      <span slot="tip">A tip.</span>
    </craft-field>
    <craft-input label="Group Name" help-text="Shown in the control panel.">
      <span slot="tip">A tip.</span>
    </craft-input>
  `;

  const field = await layout(
    document.querySelector<CraftField>('craft-field')!
  );
  const input = await layout(
    document.querySelector<CraftInput>('craft-input')!
  );

  expect(field.helpToInput).toBeGreaterThan(0);
  expect(input).toEqual(field);
});

it('adds a standalone input’s tip to its description', async () => {
  document.body.innerHTML = `
    <craft-input label="Group Name">
      <span slot="tip">Type $ to choose an environment variable.</span>
    </craft-input>
  `;
  const element = document.querySelector<CraftInput>('craft-input')!;
  const tip = element.querySelector('[slot="tip"]')!;

  await vi.waitFor(() =>
    expect(
      element
        .querySelector('input')!
        .getAttribute('aria-describedby')
        ?.split(' ')
    ).toContain(tip.id)
  );
});

it('renders no label or help text wrapper for an unlabeled input', async () => {
  document.body.innerHTML = '<craft-input aria-label="Search"></craft-input>';
  const element = document.querySelector<CraftInput>('craft-input')!;
  await element.updateComplete;

  expect(element.shadowRoot!.querySelector('.form-field__label')).toBeNull();
  expect(
    element.shadowRoot!.querySelector('.form-field__help-text')
  ).toBeNull();
});
