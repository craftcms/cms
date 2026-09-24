import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './checkbox-indeterminate.js';
import '../checkbox/checkbox.js';
import '../checkbox-group/checkbox-group.js';
import type CraftCheckboxIndeterminate from './checkbox-indeterminate.js';

/**
 * The component only means anything inside a group — that is what registers
 * the children it stands for, so every story and every real usage wraps it.
 */
async function createGroup(count = 3): Promise<CraftCheckboxIndeterminate> {
  const group = document.createElement('craft-checkbox-group');
  group.innerHTML = `
    <label slot="label">Notifications</label>
    <craft-checkbox-indeterminate>
      <label slot="label">All</label>
      ${Array.from(
        {length: count},
        (_, index) =>
          `<craft-checkbox><label slot="label">Option ${index + 1}</label></craft-checkbox>`
      ).join('')}
    </craft-checkbox-indeterminate>
  `;
  document.body.append(group);

  const element = group.querySelector(
    'craft-checkbox-indeterminate'
  ) as CraftCheckboxIndeterminate;
  await element.updateComplete;
  await new Promise((resolve) => setTimeout(resolve, 0));
  await element.updateComplete;
  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

/**
 * The tri-state itself is Lion's, and its sync runs off registration and
 * `model-value-changed` events that need a real browser to settle — the
 * Storybook stories cover it there. What is checked here is this package's own
 * contribution: that the element composes correctly inside a group, and that
 * the three states it can hold are addressable.
 */
describe('craft-checkbox-indeterminate', () => {
  it('stands for the checkboxes nested inside it', async () => {
    const element = await createGroup();

    expect(element.querySelectorAll('craft-checkbox')).toHaveLength(3);
  });

  it('starts unchecked and determinate', async () => {
    const element = await createGroup();

    expect(element.checked).toBe(false);
    expect(element.indeterminate).toBe(false);
  });

  /** The middle state is the one an ordinary checkbox cannot express. */
  it('can be put into the indeterminate state', async () => {
    const element = await createGroup();

    element.indeterminate = true;
    await element.updateComplete;

    expect(element.indeterminate).toBe(true);
  });

  it('can be checked', async () => {
    const element = await createGroup();

    element.checked = true;
    await element.updateComplete;

    expect(element.checked).toBe(true);
  });

  it('takes its own label from the label slot', async () => {
    const element = await createGroup();

    expect(
      element.querySelector(':scope > label[slot="label"]')?.textContent
    ).toBe('All');
  });
});
