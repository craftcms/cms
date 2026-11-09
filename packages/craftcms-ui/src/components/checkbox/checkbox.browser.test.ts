import {beforeEach, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import type CraftCheckbox from './checkbox.js';
import '../../styles/cp.css';
import './checkbox.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('is a Tab stop that Space toggles', async () => {
  document.body.innerHTML = '<craft-checkbox label="Agree"></craft-checkbox>';
  const checkbox = document.querySelector<CraftCheckbox>('craft-checkbox')!;
  await checkbox.updateComplete;
  const changes: boolean[] = [];
  checkbox.addEventListener('change', () => changes.push(checkbox.checked));

  await userEvent.tab();
  expect(document.activeElement).toBe(checkbox.querySelector('input'));
  expect(checkbox.hasAttribute('focused-visible')).toBe(true);

  await userEvent.keyboard(' ');
  await userEvent.keyboard(' ');

  expect(changes).toEqual([true, false]);
});

it('shows its feedback below the label and describes the input with it', async () => {
  document.body.innerHTML = `
    <craft-checkbox label="Agree" invalid>
      <div slot="feedback">You must agree to continue.</div>
    </craft-checkbox>`;
  const checkbox = document.querySelector<CraftCheckbox>('craft-checkbox')!;
  await checkbox.updateComplete;
  const input = checkbox.querySelector('input')!;
  const label = checkbox.querySelector('label')!.getBoundingClientRect();
  const feedback = checkbox.querySelector<HTMLElement>('[slot="feedback"]')!;
  const box = feedback.getBoundingClientRect();

  expect(box.height).toBeGreaterThan(0);
  expect(box.top).toBeGreaterThanOrEqual(label.bottom);
  expect(box.left).toBe(label.left);
  expect(input.getAttribute('aria-describedby')?.split(' ')).toContain(
    feedback.id
  );
});

it('toggles when its label is clicked', async () => {
  document.body.innerHTML = '<craft-checkbox label="Agree"></craft-checkbox>';
  const checkbox = document.querySelector<CraftCheckbox>('craft-checkbox')!;
  await checkbox.updateComplete;

  await userEvent.click(checkbox.querySelector('label')!);

  expect(checkbox.checked).toBe(true);
});
