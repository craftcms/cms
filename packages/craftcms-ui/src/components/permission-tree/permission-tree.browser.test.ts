import {beforeEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';
import type CraftPermissionTree from './permission-tree.js';
import './permission-tree.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('draws its checkboxes like other checkboxes', async () => {
  document.body.innerHTML = `
    <craft-checkbox label="Reference" checked></craft-checkbox>
    <craft-permission-tree></craft-permission-tree>`;
  const tree = document.querySelector<CraftPermissionTree>(
    'craft-permission-tree'
  )!;
  tree.modelValue = ['viewEntries'];
  tree.groups = [
    {
      handle: 'content',
      heading: 'Content',
      keys: ['viewEntries'],
      permissions: {
        viewEntries: {key: 'viewEntries', label: 'View entries'},
      },
    },
  ];
  await tree.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));

  const reference = getComputedStyle(
    document.querySelector('craft-checkbox > input[type="checkbox"]')!
  );
  const input = getComputedStyle(
    tree.shadowRoot!.querySelector('craft-checkbox > input[type="checkbox"]')!
  );

  expect(input.appearance).toBe('none');
  expect({
    width: input.width,
    height: input.height,
    borderRadius: input.borderRadius,
    backgroundColor: input.backgroundColor,
  }).toEqual({
    width: reference.width,
    height: reference.height,
    borderRadius: reference.borderRadius,
    backgroundColor: reference.backgroundColor,
  });
});
