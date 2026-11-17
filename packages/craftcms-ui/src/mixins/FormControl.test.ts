import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftInput from '../components/input/input.js';
import '../components/input/input.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function create(
  setup: (element: CraftInput) => void = () => {}
): Promise<CraftInput> {
  const element = document.createElement('craft-input') as CraftInput;
  setup(element);
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function labelNode(element: CraftInput): HTMLElement {
  return element.querySelector(':scope > [slot="label"]')!;
}

function rendersLabel(element: CraftInput): boolean {
  return element.shadowRoot!.querySelector('.form-field__label') !== null;
}

async function mutationsFlushed(element: CraftInput): Promise<void> {
  await new Promise((resolve) => setTimeout(resolve));
  await element.updateComplete;
}

describe('FormControl label', () => {
  it('is not rendered when there is no label', async () => {
    const element = await create((el) =>
      el.setAttribute('aria-label', 'Title')
    );

    expect(rendersLabel(element)).toBe(false);
  });

  it('is rendered when a label attribute is set', async () => {
    const element = await create((el) => el.setAttribute('label', 'Title'));

    expect(rendersLabel(element)).toBe(true);
  });

  it('ignores a blank label', async () => {
    const element = await create((el) => el.setAttribute('label', '   '));

    expect(rendersLabel(element)).toBe(false);
  });

  it('follows the label property', async () => {
    const element = await create();

    element.label = 'Title';
    await element.updateComplete;
    expect(rendersLabel(element)).toBe(true);

    element.label = '';
    await element.updateComplete;
    expect(rendersLabel(element)).toBe(false);
  });

  it('is rendered when a slotted label has text', async () => {
    const element = await create((el) => {
      const label = document.createElement('label');
      label.slot = 'label';
      label.textContent = 'Title';
      el.append(label);
    });

    expect(rendersLabel(element)).toBe(true);
  });

  it('is rendered when a slotted label only contains an element', async () => {
    const element = await create((el) => {
      const label = document.createElement('label');
      label.slot = 'label';
      label.append(document.createElement('craft-icon'));
      el.append(label);
    });

    expect(rendersLabel(element)).toBe(true);
  });

  it('follows changes to the slotted label made outside of Lit', async () => {
    const element = await create();

    labelNode(element).textContent = 'Title';
    await mutationsFlushed(element);
    expect(rendersLabel(element)).toBe(true);

    labelNode(element).textContent = '';
    await mutationsFlushed(element);
    expect(rendersLabel(element)).toBe(false);
  });
});
