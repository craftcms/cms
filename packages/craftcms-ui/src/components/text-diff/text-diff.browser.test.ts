import {afterEach, beforeAll, describe, expect, it} from 'vite-plus/test';
import {cdp, userEvent} from 'vite-plus/test/browser';
import {contrast, loadTokens} from '../../../test/contrast';
import '../../styles/cp.css';
import './text-diff.js';

it('reaches the button that shows the collapsed text, and uses it, from the keyboard', async () => {
  const filler = 'Filler words pad out this paragraph nicely. '.repeat(10);
  const element = document.createElement('craft-text-diff');
  element.current = `Old start. ${filler}`;
  element.proposed = `New start. ${filler}`;
  element.collapse = true;
  document.body.append(element);
  await element.updateComplete;

  const button =
    element.shadowRoot!.querySelector<HTMLElement>('craft-button')!;
  await userEvent.tab();

  expect(element.shadowRoot!.activeElement).toBe(button);

  await userEvent.keyboard('{Enter}');
  await element.updateComplete;

  expect(button.getAttribute('aria-expanded')).toBe('true');
});

beforeAll(loadTokens);

afterEach(async () => {
  await cdp().send('Emulation.setEmulatedMedia', {features: []});
  document.body.innerHTML = '';
});

async function renderInTheme(theme: string) {
  document.body.innerHTML = `
    <div data-theme="${theme}">
      <craft-text-diff
        current="The kitten was tucked beneath a basket, shivering."
        proposed="The kitten was shivering beneath a basket."
      ></craft-text-diff>
    </div>`;
  const element = document.querySelector('craft-text-diff')!;
  element.highlights = ['beneath'];
  await element.updateComplete;

  return (selector: string) =>
    getComputedStyle(element.shadowRoot!.querySelector(selector)!);
}

describe.each(['light', 'dark'])('in the %s theme', (theme) => {
  it('sets deleted and inserted text at 4.5:1 against their backgrounds', async () => {
    const style = await renderInTheme(theme);

    for (const selector of ['del', 'ins']) {
      expect(
        contrast(style(selector).color, style(selector).backgroundColor),
        selector
      ).toBeGreaterThanOrEqual(4.5);
    }
  });

  it.each(['default', 'raised', 'sunken'])(
    'draws the highlight’s underline at 3:1 against the %s surface',
    async (surface) => {
      const style = await renderInTheme(theme);
      const page = document.querySelector<HTMLElement>('[data-theme]')!;
      page.style.background = `var(--c-surface-${surface})`;
      const background = getComputedStyle(page).backgroundColor;

      expect(background).not.toBe('rgba(0, 0, 0, 0)');
      expect(
        contrast(style('mark').textDecorationColor, background)
      ).toBeGreaterThanOrEqual(3);
    }
  );
});

it.each(['none', 'active'])(
  'strikes through deleted text and underlines inserted text, with forced colors %s',
  async (forcedColors) => {
    await cdp().send('Emulation.setEmulatedMedia', {
      features: [{name: 'forced-colors', value: forcedColors}],
    });
    const style = await renderInTheme('light');

    expect(matchMedia(`(forced-colors: ${forcedColors})`).matches).toBe(true);
    expect(style('del').textDecorationLine).toContain('line-through');
    expect(style('ins').textDecorationLine).toContain('underline');
  }
);

it('is hidden with the `hidden` attribute', async () => {
  document.body.innerHTML = `<craft-text-diff hidden proposed="Some text."></craft-text-diff>`;
  const element = document.querySelector('craft-text-diff')!;
  await element.updateComplete;

  expect(getComputedStyle(element).display).toBe('none');
});
