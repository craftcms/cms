import {beforeEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';
import './pane.js';
import type CraftPane from './pane.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function surface(appearance: string): Promise<CSSStyleDeclaration> {
  const pane = document.createElement('craft-pane') as CraftPane;
  pane.setAttribute('appearance', appearance);
  pane.textContent = appearance;
  document.body.append(pane);
  await pane.updateComplete;

  return getComputedStyle(pane.shadowRoot!.querySelector('[part="base"]')!);
}

it('outlines and fills an outline-fill pane, without a shadow', async () => {
  const outlineFill = await surface('outline-fill');
  const raised = await surface('raised');
  const outline = await surface('outline');

  expect(outlineFill.borderTopColor).toBe(outline.borderTopColor);
  expect(outlineFill.borderTopColor).not.toBe('rgba(0, 0, 0, 0)');
  expect(outlineFill.boxShadow).toBe('none');
  expect(outlineFill.backgroundColor).not.toBe(raised.backgroundColor);
});
