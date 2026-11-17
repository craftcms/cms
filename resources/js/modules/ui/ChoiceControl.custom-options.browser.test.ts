import '../../../css/cp.css';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser/context';
import {createApp, h, nextTick, ref, type App} from 'vue';
import ChoiceControl from './ChoiceControl.vue';
import type {UiControlPayload} from './types';

let app: App | undefined;
let container: HTMLElement | undefined;

afterEach(() => {
  app?.unmount();
  container?.remove();
});

const value = ref<string | string[]>('');

async function mount(
  initial: string | string[],
  multiple = true
): Promise<unknown[]> {
  value.value = initial;
  const emitted: unknown[] = [];
  const control: UiControlPayload<any> = {
    type: 'CraftCms\\Cms\\Ui\\Controls\\Choice',
    component: 'craft:choice',
    props: {
      multiple,
      presentation: multiple ? 'checkboxes' : 'radios',
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
        value: value.value,
        editable: true,
        invalid: false,
        required: false,
        'onUpdate:value': (next: string | string[]) => {
          emitted.push(next);
          value.value = next;
        },
      }),
  });
  app.mount(container);
  await nextTick();
  await customElements.whenDefined(
    multiple ? 'craft-checkbox-group' : 'craft-radio-group'
  );

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
    (group.choices as unknown as Element[]).map((element) => element.localName)
  ).toEqual(['craft-checkbox', 'craft-checkbox', 'craft-checkbox']);
});

it('emits what’s typed into the custom radio, leaving the group to its radios', async () => {
  const emitted = await mount('a', false);
  const group = container!.querySelector('craft-radio-group')!;
  const input = container!.querySelector<HTMLInputElement>(
    '[data-custom] input[type=text]'
  )!;

  await userEvent.click(
    container!.querySelector<HTMLInputElement>(
      '[data-custom] input[type=radio]'
    )!
  );
  await userEvent.type(input, 'new');

  expect(emitted.at(-1)).toBe('new');
  expect(
    (group.choices as unknown as Element[]).map((element) => element.localName)
  ).toEqual(['craft-radio', 'craft-radio']);
});

it('shows the custom options of a value written from outside, rather than the ones it had', async () => {
  const emitted = await mount(['mine']);

  value.value = ['copied'];
  await vi.waitFor(() =>
    expect(
      [
        ...container!.querySelectorAll<HTMLInputElement>(
          '[data-custom] input[type=text]'
        ),
      ].map((input) => input.value)
    ).toEqual(['copied'])
  );
  await userEvent.click(
    container!.querySelector<HTMLInputElement>('input[type=checkbox][value=a]')!
  );

  expect(emitted.at(-1)).toEqual(['a', 'copied']);
});

it('selects the custom radio for a custom value written from outside, and leaves it for a regular one', async () => {
  await mount('a', false);
  const radio = () =>
    container!.querySelector<HTMLInputElement>(
      '[data-custom] input[type=radio]'
    )!;

  value.value = 'copied';
  await vi.waitFor(() => expect(radio().checked).toBe(true));
  expect(
    container!.querySelector<HTMLInputElement>(
      '[data-custom] input[type=text]'
    )!.value
  ).toBe('copied');

  value.value = 'a';
  await vi.waitFor(() => expect(radio().checked).toBe(false));

  value.value = 'copied';
  await vi.waitFor(() => expect(radio().checked).toBe(true));
  value.value = '';
  await vi.waitFor(() => expect(radio().checked).toBe(false));
  expect(
    container!.querySelector<HTMLInputElement>(
      '[data-custom] input[type=text]'
    )!.value
  ).toBe('');
});

it('keeps the custom radio selected while its text is cleared', async () => {
  const emitted = await mount('mine', false);
  const input = container!.querySelector<HTMLInputElement>(
    '[data-custom] input[type=text]'
  )!;

  await userEvent.clear(input);
  await nextTick();

  expect(emitted.at(-1)).toBe('');
  expect(
    container!.querySelector<HTMLInputElement>(
      '[data-custom] input[type=radio]'
    )!.checked
  ).toBe(true);
});

it('keeps a custom option that’s being typed when its text matches a regular option', async () => {
  await mount(['mine']);
  const input = container!.querySelector<HTMLInputElement>(
    '[data-custom] input[type=text]'
  )!;

  await userEvent.clear(input);
  await userEvent.type(input, 'abc');

  expect(
    [
      ...container!.querySelectorAll<HTMLInputElement>(
        '[data-custom] input[type=text]'
      ),
    ].map((custom) => custom.value)
  ).toEqual(['abc']);
  expect(document.activeElement).toBe(input);
});

it('keeps the custom radio selected while typing text that matches a regular option', async () => {
  const emitted = await mount('mine', false);
  const input = container!.querySelector<HTMLInputElement>(
    '[data-custom] input[type=text]'
  )!;

  await userEvent.clear(input);
  await userEvent.type(input, 'abc');

  expect(emitted.at(-1)).toBe('abc');
});

it('rebuilds the custom options when an Undo puts back the value they last sent', async () => {
  await mount(['mine']);
  const input = container!.querySelector<HTMLInputElement>(
    '[data-custom] input[type=text]'
  )!;
  const customTexts = () =>
    [
      ...container!.querySelectorAll<HTMLInputElement>(
        '[data-custom] input[type=text]'
      ),
    ].map((custom) => custom.value);

  await userEvent.clear(input);
  await userEvent.type(input, 'typed');
  value.value = ['copied'];
  await vi.waitFor(() => expect(customTexts()).toEqual(['copied']));
  value.value = ['typed'];

  await vi.waitFor(() => expect(customTexts()).toEqual(['typed']));
});
