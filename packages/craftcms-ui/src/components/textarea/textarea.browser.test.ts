import {beforeAll, beforeEach, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import type CraftTextarea from './textarea.js';
import './textarea.js';
import {loadTokens} from '../../../test/contrast';

beforeAll(loadTokens);

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(
  attrs: Record<string, string>,
  lines = 0
): Promise<HTMLTextAreaElement> {
  const element = document.createElement('craft-textarea') as CraftTextarea;
  element.label = 'Notes';
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  document.body.append(element);
  await element.updateComplete;

  if (lines) {
    element.value = Array.from({length: lines}, () => 'Line').join('\n');
    await element.updateComplete;
  }

  return element.querySelector('textarea')!;
}

/** The border-box height of `rows` lines of text in the textarea. */
function heightOfRows(textarea: HTMLTextAreaElement, rows: number): number {
  const style = getComputedStyle(textarea);

  return (
    rows * parseFloat(style.lineHeight) +
    parseFloat(style.paddingTop) +
    parseFloat(style.paddingBottom) +
    parseFloat(style.borderTopWidth) +
    parseFloat(style.borderBottomWidth)
  );
}

it.each([
  ['is as tall as its rows when it holds less', {rows: '3'}, 1, 3],
  ['grows with its content', {rows: '2'}, 5, 5],
  ['stops growing at max-rows', {rows: '2', 'max-rows': '4'}, 10, 4],
  [
    'keeps growing when max-rows is none',
    {rows: '2', 'max-rows': 'none'},
    40,
    40,
  ],
])('%s', async (_name, attrs, lines, rows) => {
  const textarea = await render(attrs, lines);

  expect(textarea.getBoundingClientRect().height).toBeCloseTo(
    heightOfRows(textarea, rows),
    0
  );
});

it('scrolls the content that doesn’t fit once it stops growing', async () => {
  const textarea = await render({rows: '2', 'max-rows': '4'}, 10);

  expect(textarea.scrollHeight).toBeGreaterThan(textarea.clientHeight);
});

it('grows as text is typed into it', async () => {
  const textarea = await render({rows: '2'});

  await userEvent.type(textarea, 'One{Enter}Two{Enter}Three{Enter}Four');

  expect(textarea.getBoundingClientRect().height).toBeCloseTo(
    heightOfRows(textarea, 4),
    0
  );
});
