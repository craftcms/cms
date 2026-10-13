import {afterEach, expect, it, vi} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import {InputRange} from 'dom-input-range';
import '../textarea/textarea.js';
import './text-expander.js';
import '../../styles/cp.css';

afterEach(() => {
  document.body.innerHTML = '';
});

it('keeps caret measurements from obscuring a slotted textarea while expanding and typing', async () => {
  document.body.innerHTML = `
    <craft-textarea label="Template">
      <textarea id="template" slot="input"></textarea>
    </craft-textarea>
    <craft-text-expander for="template"></craft-text-expander>`;
  const control = document.querySelector('craft-textarea')!;
  const textarea = document.querySelector('textarea')!;
  const expander = document.querySelector('craft-text-expander')!;
  expander.triggers = [
    {
      trigger: '{',
      boundary: 'anywhere',
      options: [{label: 'Email', value: '{email}'}],
    },
  ];
  await control.updateComplete;
  await expander.updateComplete;
  await userEvent.fill(textarea, '{');
  await vi.waitFor(() =>
    expect(
      expander.querySelector('craft-option')?.getAttribute('aria-selected')
    ).toBe('true')
  );

  const measurement = new InputRange(textarea).getStyleClone().element
    .parentElement!;
  expect(getComputedStyle(measurement).visibility).toBe('hidden');
  expect(measurement.getBoundingClientRect().width).toBeGreaterThan(0);

  await userEvent.keyboard('{Enter}');
  await userEvent.keyboard('x');
  expect(textarea.value).toBe('{email}x');
  expect(document.activeElement).toBe(textarea);
});
