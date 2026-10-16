import {afterEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';
import './select-color.js';
import type CraftSelectColor from './select-color.js';

afterEach(() => {
  document.body.innerHTML = '';
  delete document.documentElement.dataset.theme;
});

async function swatchEdges(): Promise<Record<string, string>> {
  document.body.innerHTML = `<craft-select-color allow-transparent></craft-select-color>`;
  const select =
    document.querySelector<CraftSelectColor>('craft-select-color')!;
  await select.updateComplete;
  const edge = (value: string) => {
    const option = [
      ...select.shadowRoot!.querySelectorAll('craft-option'),
    ].find(
      (o) => (o as unknown as {choiceValue: string}).choiceValue === value
    )!;

    return getComputedStyle(option.querySelector('.select-color__swatch')!)
      .boxShadow;
  };

  return {blank: edge('__blank__'), black: edge('black'), blue: edge('blue')};
}

it('gives the no-color and black swatches a light edge in the dark theme', async () => {
  const light = await swatchEdges();

  expect(light.blank).toBe(light.blue);
  expect(light.black).toBe(light.blue);

  document.documentElement.dataset.theme = 'dark';
  const dark = await swatchEdges();

  expect(dark.blank).not.toBe(dark.blue);
  expect(dark.black).toBe(dark.blank);
  expect(dark.blue).toBe(light.blue);
});
