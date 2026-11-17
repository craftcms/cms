import {beforeEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';
import './callout.js';
import type CraftCallout from './callout.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('adds no space below an inline status in a footer row', async () => {
  document.body.innerHTML = `
    <div style="display: flex; justify-content: space-between; align-items: center; inline-size: 40rem">
      <button type="button">Save</button>
      <div style="display: flex; align-items: center">
        <craft-callout inline padding="none" appearance="plain" variant="success" size="small">Saved 11:02 AM</craft-callout>
      </div>
    </div>`;
  const callout = document.querySelector<CraftCallout>('craft-callout')!;
  await callout.updateComplete;
  const base = callout.shadowRoot!.querySelector('.callout')!;
  const body = callout.shadowRoot!.querySelector('.callout__body')!;
  const {borderBlockStartWidth, borderBlockEndWidth} = getComputedStyle(base);

  expect(
    Math.round(
      base.getBoundingClientRect().height -
        body.getBoundingClientRect().height -
        parseFloat(borderBlockStartWidth) -
        parseFloat(borderBlockEndWidth)
    )
  ).toBe(0);
});
