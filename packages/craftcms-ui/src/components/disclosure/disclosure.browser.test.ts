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

it.each([['xsmall'], ['small'], ['medium'], ['large']])(
  'centers the hairline beneath the %s invoker’s chevron',
  async (size) => {
    document.body.innerHTML = `
      <craft-disclosure label="Details" size="${size}" opened>
        <div slot="content">Content</div>
      </craft-disclosure>`;
    const disclosure =
      document.querySelector<CraftDisclosure>('craft-disclosure')!;
    await disclosure.updateComplete;
    await settle();

    const invoker = disclosure.querySelector('craft-button')!;
    const chevron = invoker.shadowRoot!.querySelector('craft-icon')!;
    const content = disclosure.querySelector<HTMLElement>('[slot="content"]')!;

    const chevronBox = chevron.getBoundingClientRect();
    const chevronCenter = chevronBox.left + chevronBox.width / 2;
    const hairlineCenter = content.getBoundingClientRect().left + 0.5;

    expect(hairlineCenter).toBeCloseTo(chevronCenter, 1);
  }
);
