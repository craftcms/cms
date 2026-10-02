import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftDialog from './dialog.js';
import './dialog.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('centers a modal dialog in the viewport', async () => {
  const dialog = document.createElement('craft-dialog') as CraftDialog;
  dialog.label = 'Centered dialog';
  dialog.textContent = 'Dialog content';
  document.body.append(dialog);
  dialog.opened = true;
  await dialog.updateComplete;

  const bounds = dialog
    .shadowRoot!.querySelector('dialog')!
    .getBoundingClientRect();

  expect(
    Math.abs(bounds.left + bounds.width / 2 - window.innerWidth / 2)
  ).toBeLessThan(1);
  expect(
    Math.abs(bounds.top + bounds.height / 2 - window.innerHeight / 2)
  ).toBeLessThan(1);
});
