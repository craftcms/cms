import {beforeEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';
import '../button/button.js';
import '../icon/icon.js';
import './disclosure.js';
import type CraftDisclosure from './disclosure.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

const settle = () => new Promise((resolve) => setTimeout(resolve, 50));

it.each([['ltr'], ['rtl']])(
  'starts the hairline at the disclosure’s start edge, not inset (%s)',
  async (dir) => {
    document.body.innerHTML = `
      <craft-disclosure label="Details" dir="${dir}" opened>
        <div slot="content">Content</div>
      </craft-disclosure>`;
    const disclosure =
      document.querySelector<CraftDisclosure>('craft-disclosure')!;
    await disclosure.updateComplete;
    await settle();

    const content = disclosure.querySelector<HTMLElement>('[slot="content"]')!;
    const host = disclosure.getBoundingClientRect();
    const box = content.getBoundingClientRect();

    expect(getComputedStyle(content).borderInlineStartWidth).toBe('1px');
    if (dir === 'rtl') {
      expect(box.right).toBe(host.right);
    } else {
      expect(box.left).toBe(host.left);
    }
  }
);

it('puts the chevron after the label', async () => {
  document.body.innerHTML = `<craft-disclosure label="Details"></craft-disclosure>`;
  const disclosure =
    document.querySelector<CraftDisclosure>('craft-disclosure')!;
  await disclosure.updateComplete;
  const invoker = disclosure.querySelector('craft-button')!;
  await invoker.updateComplete;
  await settle();

  const chevron = invoker.shadowRoot!.querySelector('craft-icon')!;
  const label = invoker.shadowRoot!.querySelector('.label')!;

  expect(chevron.closest('slot')?.getAttribute('name')).toBe('suffix');
  expect(chevron.getBoundingClientRect().left).toBeGreaterThan(
    label.getBoundingClientRect().left
  );
});

it('points the closed chevron left in right-to-left text', async () => {
  document.body.innerHTML = `<craft-disclosure label="Details" dir="rtl"></craft-disclosure>`;
  const disclosure =
    document.querySelector<CraftDisclosure>('craft-disclosure')!;
  await disclosure.updateComplete;

  expect(disclosure.querySelector('craft-button')!.icon).toBe('chevron-left');

  disclosure.opened = true;
  await disclosure.updateComplete;

  expect(disclosure.querySelector('craft-button')!.icon).toBe('chevron-down');
});

it('fires craft-hide and craft-show on itself as its slotted content toggles', async () => {
  document.body.innerHTML = `
    <craft-disclosure label="Details" opened>
      <div slot="content">Content</div>
    </craft-disclosure>`;
  const disclosure =
    document.querySelector<CraftDisclosure>('craft-disclosure')!;
  const onHost: string[] = [];
  const bubbled: string[] = [];
  for (const type of ['craft-show', 'craft-hide']) {
    disclosure.addEventListener(type, () => onHost.push(type));
    document.body.addEventListener(type, () => bubbled.push(type));
  }
  await disclosure.updateComplete;
  await settle();

  const invoker = disclosure.querySelector<HTMLElement>('[slot="invoker"]')!;
  invoker.click();
  await disclosure.updateComplete;
  invoker.click();
  await disclosure.updateComplete;

  expect(onHost).toEqual(['craft-hide', 'craft-show']);
  expect(bubbled).toEqual([]);
});
