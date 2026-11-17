import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftCombobox from './combobox.js';
import './combobox.js';
import '../../styles/cp.css';

beforeEach(() => {
  document.body.innerHTML = '';
});

it.each(['light', 'dark'])(
  'does not paint the open list over the overlay surface in the %s theme',
  async (theme) => {
    document.body.innerHTML = `<div data-theme="${theme}"></div>`;
    const control = document.createElement('craft-combobox') as CraftCombobox;
    control.options = [{label: 'One', value: '1'}];
    document.querySelector('[data-theme]')!.append(control);
    await control.updateComplete;
    control.open = true;
    await control.updateComplete;

    const listbox = control.querySelector('[slot="listbox"]')!;
    const overlay = control.shadowRoot!.querySelector(
      '#overlay-content-node-wrapper'
    )!;

    // Lion paints the list white, which hides a dark overlay surface.
    expect([
      getComputedStyle(overlay).backgroundColor,
      'rgba(0, 0, 0, 0)',
    ]).toContain(getComputedStyle(listbox).backgroundColor);
  }
);
