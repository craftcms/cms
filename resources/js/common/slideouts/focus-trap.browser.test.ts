import {afterEach, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser/context';
import {releaseFocusWithin, trapFocusWithin} from '@craftcms/garnish';
import type CraftCombobox from '@craftcms/ui/components/combobox/combobox';
import '@craftcms/ui/components/combobox/combobox';

let dialog: HTMLElement | undefined;

afterEach(() => {
  if (dialog) {
    releaseFocusWithin(dialog);
    dialog = undefined;
  }
  document.body.innerHTML = '';
});

/** The focused element, looking inside shadow roots. */
function focused(): Element | null {
  let active = document.activeElement;
  while (active?.shadowRoot?.activeElement) {
    active = active.shadowRoot.activeElement;
  }
  return active;
}

it('reaches a clearable combobox’s Clear button before wrapping, when the combobox comes last', async () => {
  document.body.innerHTML = `
    <button type="button">Behind</button>
    <div class="dialog">
      <button type="button" class="first">First</button>
      <label id="fruit-label">Fruit</label>
    </div>`;
  dialog = document.querySelector('.dialog')!;
  const combobox = document.createElement('craft-combobox') as CraftCombobox;
  combobox.setAttribute('aria-labelledby', 'fruit-label');
  combobox.options = [{label: 'Apple', value: 'apple'}];
  combobox.clearable = true;
  combobox.setAttribute('value', 'apple');
  dialog.append(combobox);
  await combobox.updateComplete;
  trapFocusWithin(dialog);
  const clear = combobox.shadowRoot!.querySelector('.clear')!;
  const first = dialog.querySelector<HTMLElement>('.first')!;

  combobox.querySelector('input')!.focus();
  await userEvent.tab();
  expect(focused()).toBe(clear);

  await userEvent.tab();
  expect(focused()).toBe(first);

  await userEvent.tab({shift: true});
  expect(focused()).toBe(clear);
});
