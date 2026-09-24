import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftCheckbox from '../checkbox/checkbox.js';
import type CraftPermissionTree from './permission-tree.js';
import './permission-tree.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-permission-tree', () => {
  it('renders groups, locks inherited permissions, and clears nested selections', async () => {
    const element = document.createElement(
      'craft-permission-tree'
    ) as CraftPermissionTree;
    element.name = 'permissions';
    element.modelValue = ['viewEntries', 'editEntries'];
    element.lockedPermissions = ['deleteentries'];
    element.groups = [
      {
        handle: 'content',
        heading: 'Content',
        keys: ['viewEntries', 'editEntries', 'deleteEntries'],
        permissions: {
          viewEntries: {
            key: 'viewEntries',
            label: 'View entries',
            nested: {
              editEntries: {key: 'editEntries', label: 'Edit entries'},
              deleteEntries: {key: 'deleteEntries', label: 'Delete entries'},
            },
          },
        },
      },
      {
        handle: 'users',
        heading: 'Users',
        keys: ['viewUsers'],
        permissions: {
          viewUsers: {key: 'viewUsers', label: 'View users'},
        },
      },
    ];
    document.body.append(element);
    await element.updateComplete;

    expect(element.shadowRoot!.querySelectorAll('.group')).toHaveLength(2);

    const checkboxes = [
      ...element.shadowRoot!.querySelectorAll<CraftCheckbox>('craft-checkbox'),
    ];
    const view = checkboxes.find(
      (checkbox) => checkbox.choiceValue === 'viewEntries'
    )!;
    const inherited = checkboxes.find(
      (checkbox) => checkbox.choiceValue === 'deleteEntries'
    )!;

    expect(inherited.checked).toBe(true);
    expect(inherited.disabled).toBe(true);
    expect(
      [...element.querySelectorAll<HTMLInputElement>('input')].map((input) => [
        input.name,
        input.value,
      ])
    ).toEqual([
      ['permissions', ''],
      ['permissions[]', 'viewEntries'],
      ['permissions[]', 'editEntries'],
    ]);

    view.checked = false;
    view.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true, composed: true})
    );
    await element.updateComplete;

    expect(element.modelValue).toEqual([]);
    expect(
      [...element.querySelectorAll<HTMLInputElement>('input')].map(
        (input) => input.value
      )
    ).toEqual(['']);
  });

  it('emits native input and change when a permission is toggled', async () => {
    const element = document.createElement(
      'craft-permission-tree'
    ) as CraftPermissionTree;
    element.name = 'permissions';
    element.modelValue = ['viewEntries'];
    element.groups = [
      {
        handle: 'content',
        heading: 'Content',
        keys: ['viewEntries'],
        permissions: {
          viewEntries: {key: 'viewEntries', label: 'View entries'},
        },
      },
    ];
    document.body.append(element);
    await element.updateComplete;

    const order: string[] = [];
    element.addEventListener('input', () => order.push('input'));
    element.addEventListener('change', () => order.push('change'));

    const checkbox =
      element.shadowRoot!.querySelector<CraftCheckbox>('craft-checkbox')!;
    checkbox.checked = false;
    checkbox.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true, composed: true})
    );
    await element.updateComplete;

    // Each toggle is a commit, so the pair fires together.
    expect(order).toEqual(['input', 'change']);
    expect(element.modelValue).toEqual([]);
  });
});
