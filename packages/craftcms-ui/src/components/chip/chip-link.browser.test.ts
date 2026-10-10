import {beforeEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';
import '../truncate/truncate.js';
import './chip.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

function linkColor(): string {
  const link = document.createElement('a');
  link.href = '#';
  document.body.append(link);
  const color = getComputedStyle(link).color;
  link.remove();

  return color;
}

it('colors a chip inside a link as a link', async () => {
  document.body.innerHTML = `
    <a href="#">
      <craft-chip><craft-truncate><span id="label">Entry</span></craft-truncate></craft-chip>
    </a>
    <craft-chip><span id="plain">Entry</span></craft-chip>`;
  for (const chip of document.querySelectorAll('craft-chip')) {
    await (chip as HTMLElement & {updateComplete: Promise<unknown>})
      .updateComplete;
  }

  const color = linkColor();

  expect(getComputedStyle(document.getElementById('label')!).color).toBe(color);
  expect(getComputedStyle(document.getElementById('plain')!).color).not.toBe(
    color
  );
});
