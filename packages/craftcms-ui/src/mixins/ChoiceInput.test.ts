import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftCheckbox from '../components/checkbox/checkbox.js';
import type CraftRadio from '../components/radio/radio.js';
import '../components/checkbox/checkbox.js';
import '../components/radio/radio.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it.each(['craft-checkbox', 'craft-radio'])(
  '%s posts the value it reports, an empty one included',
  async (tag) => {
    const template = document.createElement('template');
    template.innerHTML = `
      <form>
        <${tag} name="missing" checked></${tag}>
        <${tag} name="empty" value="" checked></${tag}>
        <${tag} name="given" value="x" checked></${tag}>
        <${tag} name="emptied" value="x" checked></${tag}>
        <${tag}>
          <input slot="input" type="${tag.slice(6)}" name="server" value="s" checked>
        </${tag}>
      </form>
    `;
    document.body.append(template.content);
    const choices = [
      ...document.querySelectorAll<CraftCheckbox | CraftRadio>(tag),
    ];
    await Promise.all(choices.map((choice) => choice.updateComplete));

    choices[3]!.value = '';
    await choices[3]!.updateComplete;

    const form = document.querySelector('form')!;
    expect(choices.map((choice) => choice.value)).toEqual([
      'on',
      '',
      'x',
      '',
      's',
    ]);
    expect([...new FormData(form)]).toEqual([
      ['missing', 'on'],
      ['empty', ''],
      ['given', 'x'],
      ['emptied', ''],
      ['server', 's'],
    ]);
  }
);
