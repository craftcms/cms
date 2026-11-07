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

it('toggles when its label is clicked', async () => {
  document.body.innerHTML = '<craft-checkbox label="Agree"></craft-checkbox>';
  const checkbox = document.querySelector<CraftCheckbox>('craft-checkbox')!;
  await checkbox.updateComplete;

  await userEvent.click(checkbox.querySelector('label')!);

  expect(checkbox.checked).toBe(true);
});
