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

async function mutationsFlushed(): Promise<void> {
  await new Promise((resolve) => setTimeout(resolve));
}

describe('HasLabel', () => {
  it('is absent when there is no label', async () => {
    const element = await create((el) =>
      el.setAttribute('aria-label', 'Title')
    );

    expect(element.hasAttribute('has-label')).toBe(false);
  });

  it('is present when a label attribute is set', async () => {
    const element = await create((el) => el.setAttribute('label', 'Title'));

    expect(element.hasAttribute('has-label')).toBe(true);
  });

  it('ignores a blank label', async () => {
    const element = await create((el) => el.setAttribute('label', '   '));

    expect(element.hasAttribute('has-label')).toBe(false);
  });

  it('follows the label property', async () => {
    const element = await create();

    element.label = 'Title';
    await element.updateComplete;
    expect(element.hasAttribute('has-label')).toBe(true);

    element.label = '';
    await element.updateComplete;
    expect(element.hasAttribute('has-label')).toBe(false);
  });

  it('is present when a slotted label has text', async () => {
    const element = await create((el) => {
      const label = document.createElement('label');
      label.slot = 'label';
      label.textContent = 'Title';
      el.append(label);
    });

    expect(element.hasAttribute('has-label')).toBe(true);
  });

  it('is present when a slotted label only contains an element', async () => {
    const element = await create((el) => {
      const label = document.createElement('label');
      label.slot = 'label';
      label.append(document.createElement('craft-icon'));
      el.append(label);
    });

    expect(element.hasAttribute('has-label')).toBe(true);
  });

  it('follows changes to the slotted label made outside of Lit', async () => {
    const element = await create();

    labelNode(element).textContent = 'Title';
    await mutationsFlushed();
    expect(element.hasAttribute('has-label')).toBe(true);

    labelNode(element).textContent = '';
    await mutationsFlushed();
    expect(element.hasAttribute('has-label')).toBe(false);
  });
});
