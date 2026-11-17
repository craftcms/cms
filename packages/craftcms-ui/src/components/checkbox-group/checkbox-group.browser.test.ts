import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftCheckboxGroup from './checkbox-group.js';
import './checkbox-group.js';
import '../checkbox/checkbox.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('leaves options disabled in its markup disabled when a group that arrives disabled is re-enabled', async () => {
  const template = document.createElement('template');
  template.innerHTML = `
    <craft-checkbox-group label="Kinds" disabled>
      <craft-checkbox>
        <input slot="input" type="checkbox" name="kinds[]" value="image" disabled>
      </craft-checkbox>
      <craft-checkbox disabled>
        <input slot="input" type="checkbox" name="kinds[]" value="video">
      </craft-checkbox>
      <craft-checkbox>
        <input slot="input" type="checkbox" name="kinds[]" value="audio">
      </craft-checkbox>
    </craft-checkbox-group>
  `;
  document.body.append(template.content);
  const group = document.querySelector(
    'craft-checkbox-group'
  ) as CraftCheckboxGroup;
  await group.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));

  group.disabled = false;
  await group.updateComplete;

  expect(
    [...group.querySelectorAll('input')].map((input) => input.disabled)
  ).toEqual([true, true, false]);
});
