import {beforeEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';
import '../input/input.js';
import './select.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

const luminance = (color: string): number => {
  const [r, g, b] = color
    .match(/[\d.]+/g)!
    .slice(0, 3)
    .map((value) => {
      const channel = Number(value) / 255;
      return channel <= 0.03928
        ? channel / 12.92
        : ((channel + 0.055) / 1.055) ** 2.4;
    });

  return 0.2126 * r! + 0.7152 * g! + 0.0722 * b!;
};

it('draws native controls for the dark theme', async () => {
  document.body.innerHTML = `
    <div data-theme="dark">
      <craft-select>
        <select slot="input"><option>Monday</option></select>
      </craft-select>
      <craft-input>
        <input slot="input" type="date" />
      </craft-input>
    </div>`;
  const hosts = [
    document.querySelector('craft-select')!,
    document.querySelector('craft-input')!,
  ];
  await Promise.all(hosts.map((host) => host.updateComplete));

  for (const control of document.querySelectorAll('select, input')) {
    const style = getComputedStyle(control);

    expect(style.colorScheme, control.localName).toBe('dark');
    expect(luminance(style.color), control.localName).toBeGreaterThan(0.5);
  }
});

it('gives options an opaque background under their light text in the dark theme', async () => {
  // Chrome on Windows draws the option list on the transparent select's
  // background, falling back to white, so options need their own.
  document.body.innerHTML = `
    <div data-theme="dark">
      <craft-select>
        <select slot="input">
          <optgroup label="Days"><option>Monday</option></optgroup>
        </select>
      </craft-select>
    </div>`;
  await document.querySelector('craft-select')!.updateComplete;

  for (const element of document.querySelectorAll('option, optgroup')) {
    const style = getComputedStyle(element);

    expect(style.backgroundColor, element.localName).not.toMatch(
      /^rgba\(0, 0, 0, 0\)$|\/ 0\)$|^transparent$/
    );
    expect(luminance(style.color), element.localName).toBeGreaterThan(0.5);
  }
});

it('keeps native controls light in the light theme', async () => {
  document.body.innerHTML = `
    <div data-theme="light">
      <craft-select>
        <select slot="input"><option>Monday</option></select>
      </craft-select>
    </div>`;
  await document.querySelector('craft-select')!.updateComplete;

  const style = getComputedStyle(document.querySelector('select')!);

  expect(style.colorScheme).toBe('light');
  expect(luminance(style.color)).toBeLessThan(0.1);
});
