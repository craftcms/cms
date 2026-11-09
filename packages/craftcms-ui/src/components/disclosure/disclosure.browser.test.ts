import {beforeEach, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
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
      <craft-disclosure label="Details" dir="${dir}" open>
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

  disclosure.open = true;
  await disclosure.updateComplete;

  expect(disclosure.querySelector('craft-button')!.icon).toBe('chevron-down');
});

it('fires craft-hide and craft-show on itself as its slotted content toggles', async () => {
  document.body.innerHTML = `
    <craft-disclosure label="Details" open>
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

it('toggles from the keyboard, leaving focus on the invoker', async () => {
  document.body.innerHTML = `
    <craft-disclosure label="Details">
      <div slot="content"><a href="#inside">Inside</a></div>
    </craft-disclosure>
    <a href="#after">After</a>`;
  const disclosure =
    document.querySelector<CraftDisclosure>('craft-disclosure')!;
  await disclosure.updateComplete;
  await settle();

  const invoker = disclosure.querySelector<HTMLElement>('[slot="invoker"]')!;
  invoker.focus();

  await userEvent.keyboard('{Enter}');
  await expect.poll(() => disclosure.open).toBe(true);
  expect(invoker.matches(':focus-within, :focus')).toBe(true);

  // The expanded content follows the invoker in the Tab order.
  await userEvent.keyboard('{Tab}');
  expect(document.activeElement?.textContent).toBe('Inside');

  invoker.focus();
  await userEvent.keyboard(' ');
  await expect.poll(() => disclosure.open).toBe(false);
  expect(invoker.matches(':focus-within, :focus')).toBe(true);

  // Collapsed content leaves the Tab order.
  await userEvent.keyboard('{Tab}');
  expect(document.activeElement?.textContent).toBe('After');
});

it('wires up an invoker and content slotted in after it connected', async () => {
  document.body.innerHTML = `
    <craft-disclosure>
      <button slot="invoker" type="button">More</button>
      <div slot="content">Old</div>
    </craft-disclosure>`;
  const disclosure =
    document.querySelector<CraftDisclosure>('craft-disclosure')!;
  await disclosure.updateComplete;
  await settle();

  const invoker = document.createElement('button');
  invoker.slot = 'invoker';
  invoker.type = 'button';
  invoker.textContent = 'Details';
  const content = document.createElement('div');
  content.slot = 'content';
  content.textContent = 'New';
  disclosure.replaceChildren(invoker, content);
  await settle();

  expect(invoker.getAttribute('aria-controls')).toBe(content.id);
  expect(invoker.getAttribute('aria-expanded')).toBe('false');
  expect(getComputedStyle(content).display).toBe('none');

  invoker.click();
  await disclosure.updateComplete;

  expect(disclosure.opened).toBe(true);
  expect(getComputedStyle(content).display).not.toBe('none');
});
