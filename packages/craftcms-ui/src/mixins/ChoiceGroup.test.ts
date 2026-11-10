import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftCheckboxGroup from '../components/checkbox-group/checkbox-group.js';
import type CraftRadioGroup from '../components/radio-group/radio-group.js';
import '../components/checkbox-group/checkbox-group.js';
import '../components/checkbox/checkbox.js';
import '../components/radio-group/radio-group.js';
import '../components/radio/radio.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it.each([
  ['craft-checkbox-group', 'craft-checkbox', 'checkbox', 'kinds[]'],
  ['craft-radio-group', 'craft-radio', 'radio', 'kind'],
])(
  '%s stops posting once the name it took from its inputs is cleared',
  async (group, choice, type, name) => {
    const template = document.createElement('template');
    template.innerHTML = `
      <form>
        <${group} label="Kinds">
          <${choice}>
            <input slot="input" type="${type}" name="${name}" value="image" checked>
          </${choice}>
          <${choice}>
            <input slot="input" type="${type}" name="${name}" value="video">
          </${choice}>
        </${group}>
      </form>
    `;
    document.body.append(template.content);
    const element = document.querySelector<
      CraftCheckboxGroup | CraftRadioGroup
    >(group)!;
    const form = document.querySelector('form')!;
    await element.updateComplete;
    expect(element.name).toBe(name);

    element.name = '';
    await element.updateComplete;
    await element.updateComplete;

    expect(element.name).toBe('');
    expect([...new FormData(form)]).toEqual([]);

    element.name = 'other';
    await element.updateComplete;
    await element.updateComplete;

    expect([...new FormData(form)]).toEqual([['other', 'image']]);
  }
);
