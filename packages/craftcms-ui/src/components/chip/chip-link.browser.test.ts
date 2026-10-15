import {beforeEach, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
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

it('underlines a chip inside a link as a link', async () => {
  document.body.innerHTML = `
    <a href="#" id="link">
      <craft-chip><craft-truncate id="label"><span>Entry</span></craft-truncate></craft-chip>
    </a>
    <p id="elsewhere">Elsewhere</p>`;
  const chip = document.querySelector('craft-chip') as HTMLElement & {
    updateComplete: Promise<unknown>;
  };
  await chip.updateComplete;
  const label = document.getElementById('label')!;
  const decoration = () => getComputedStyle(label).textDecorationLine;

  expect(decoration()).toBe('none');
  await userEvent.hover(document.getElementById('link')!);
  expect(decoration()).toBe('underline');

  await userEvent.hover(document.getElementById('elsewhere')!);
  document.body.classList.add('underline-links');
  expect(decoration()).toBe('underline');
  await userEvent.hover(document.getElementById('link')!);
  expect(decoration()).toBe('none');
  document.body.classList.remove('underline-links');
});

it('gives a non-white chip’s links its text color and an underline until hovered', async () => {
  document.body.innerHTML = `
    <craft-chip id="color" data-color="red">
      <craft-truncate><a href="#">Nested</a></craft-truncate>
    </craft-chip>
    <craft-chip id="variant" variant="danger"><a href="#">Direct</a></craft-chip>
    <craft-chip id="gray" data-color="gray"><a href="#">Gray</a></craft-chip>
    <craft-chip id="neutral" variant="neutral"><a href="#">Neutral</a></craft-chip>
    <craft-chip id="default"><a href="#">Default</a></craft-chip>`;
  for (const chip of document.querySelectorAll('craft-chip')) {
    await (chip as HTMLElement & {updateComplete: Promise<unknown>})
      .updateComplete;
  }

  // Blue falls short of contrast against the gray and slate fills, so only a
  // white chip keeps it.
  for (const id of ['color', 'variant', 'gray', 'neutral']) {
    const chip = document.getElementById(id)!;
    const link = chip.querySelector('a')!;
    const body = chip.shadowRoot!.querySelector('.cp-chip')!;

    expect(getComputedStyle(link).color).toBe(getComputedStyle(body).color);
    expect(getComputedStyle(link).textDecorationLine).toBe('underline');

    await userEvent.hover(link);
    expect(getComputedStyle(link).textDecorationLine).toBe('none');
    await userEvent.unhover(link);
  }

  const plain = document.querySelector<HTMLElement>('#default a')!;
  expect(getComputedStyle(plain).color).toBe(linkColor());
  expect(getComputedStyle(plain).textDecorationLine).toBe('none');
});
