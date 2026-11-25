import {expect, it} from 'vite-plus/test';
import './empty.js';
import type CraftEmpty from './empty.js';

it('draws a quiet border around an outlined empty state, and none otherwise', async () => {
  await import('../../styles/shared/color-palette.css');
  await import('../../styles/shared/colorable.css');
  await import('../../styles/shared/variables.css');
  await import('../../styles/shared/tokens.css');

  document.body.innerHTML = `
    <span id="quiet" style="color: var(--c-color-border-quiet)"></span>
    <craft-empty id="outline" label="Nothing yet." appearance="outline"></craft-empty>
    <craft-empty id="plain" label="Nothing yet."></craft-empty>`;
  const outline = document.querySelector<CraftEmpty>('#outline')!;
  const plain = document.querySelector<CraftEmpty>('#plain')!;
  await outline.updateComplete;
  await plain.updateComplete;
  const borderColor = (element: Element) =>
    getComputedStyle(element.shadowRoot!.querySelector('.cp-empty')!)
      .borderTopColor;

  expect(borderColor(outline)).toBe(
    getComputedStyle(document.getElementById('quiet')!).color
  );
  expect(borderColor(plain)).toBe('rgba(0, 0, 0, 0)');
});

it('draws a dashed form-control border around a dashed empty state', async () => {
  await import('../../styles/shared/color-palette.css');
  await import('../../styles/shared/colorable.css');
  await import('../../styles/shared/variables.css');
  await import('../../styles/shared/tokens.css');

  document.body.innerHTML = `
    <span id="control" style="color: var(--c-form-control-border-color)"></span>
    <craft-empty id="dashed" label="Nothing yet." appearance="dashed"></craft-empty>`;
  const dashed = document.querySelector<CraftEmpty>('#dashed')!;
  await dashed.updateComplete;
  const style = getComputedStyle(
    dashed.shadowRoot!.querySelector('.cp-empty')!
  );

  expect(style.borderTopStyle).toBe('dashed');
  expect(style.borderTopColor).toBe(
    getComputedStyle(document.getElementById('control')!).color
  );
});
