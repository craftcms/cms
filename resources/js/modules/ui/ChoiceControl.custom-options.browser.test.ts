import '../../../css/cp.css';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser/context';
import {createApp, h, nextTick, type App} from 'vue';
import ChoiceControl from './ChoiceControl.vue';
import type {UiControlPayload} from './types';

let app: App | undefined;
let container: HTMLElement | undefined;

afterEach(() => {
  app?.unmount();
  container?.remove();
});

async function mount(value: string[]): Promise<unknown[]> {
  const emitted: unknown[] = [];
  const control: UiControlPayload<any> = {
    type: 'CraftCms\\Cms\\Ui\\Controls\\Choice',
    component: 'craft:choice',
    props: {
      multiple: true,
      presentation: 'checkboxes',
      allowCustom: true,
      options: [
        {label: 'A', value: 'a'},
        {label: 'mine', value: 'mine', custom: true},
      ],
    },
    path: ['options'],
    mode: 'editable',
    deltaGroup: ['options'],
  };
  container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    render: () =>
      h(ChoiceControl, {
        control,
        value,
        editable: true,
        invalid: false,
        required: false,
        'onUpdate:value': (next: unknown) => emitted.push(next),
      }),
  });
  app.mount(container);
  await nextTick();
  await customElements.whenDefined('craft-checkbox-group');

  return emitted;
}

it('adds a custom checkbox whose text is checked and emitted, leaving the group to its checkboxes', async () => {
  const emitted = await mount(['mine']);
  const group = container!.querySelector('craft-checkbox-group')!;

  container!
    .querySelector<HTMLElement>('craft-button[data-add-option]')!
    .click();
  await vi.waitFor(() =>
    expect(container!.querySelectorAll('[data-custom]')).toHaveLength(2)
  );
  const input = container!.querySelectorAll<HTMLInputElement>(
    '[data-custom] input[type=text]'
  )[1]!;
  await vi.waitFor(() => expect(document.activeElement).toBe(input));
  await userEvent.type(input, 'new');

  expect(emitted.at(-1)).toEqual(['mine', 'new']);
  expect(
    (group as HTMLElement & {formElements: HTMLElement[]}).formElements.map(
      (element) => element.tagName.toLowerCase()
    )
  ).toEqual(['craft-checkbox', 'craft-checkbox', 'craft-checkbox']);
});
