import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import type CraftInput from '@craftcms/ui/components/input/input';
import NumberControl from './NumberControl.vue';
import type {TextControlProps, UiControlPayload, UiValue} from './types';

describe('NumberControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  async function mountNumber(props: TextControlProps, value: UiValue) {
    const updates: unknown[] = [];
    const control: UiControlPayload<TextControlProps> = {
      type: 'CraftCms\\Cms\\Ui\\Controls\\Number',
      component: 'craft:number',
      props,
      path: ['fields', 'weight'],
      mode: 'editable',
      deltaGroup: ['fields', 'weight'],
    };
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(NumberControl, {
          control,
          value,
          editable: true,
          invalid: false,
          required: false,
          'onUpdate:value': (update: unknown) => updates.push(update),
        }),
    });
    app.mount(container);
    await nextTick();

    const input = container.querySelector<CraftInput>('craft-input')!;
    await input.updateComplete;

    return {
      input: input.querySelector<HTMLInputElement>('input')!,
      locale: container.querySelector<HTMLInputElement>('input[type="hidden"]'),
      updates,
    };
  }

  it('shows a number formatted for its locale, and posts and reports it with the locale', async () => {
    const {input, locale, updates} = await mountNumber(
      {inputType: 'text', locale: 'de-DE'},
      {value: '1.234,5', locale: 'de-DE'}
    );

    expect(input.type).toBe('text');
    expect(input.name).toBe('fields[weight][value]');
    expect(input.value).toBe('1.234,5');
    expect(locale?.name).toBe('fields[weight][locale]');
    expect(locale?.value).toBe('de-DE');

    // Letters are dropped as they're typed, keeping the caret where it was.
    input.focus();
    input.value = '1.2x34,5';
    input.setSelectionRange(4, 4);
    input.dispatchEvent(new InputEvent('input', {bubbles: true}));

    expect(input.value).toBe('1.234,5');
    expect(input.selectionStart).toBe(3);

    input.value = '';
    input.dispatchEvent(new InputEvent('input', {bubbles: true}));

    expect(updates).toEqual([
      {value: '1.234,5', locale: 'de-DE'},
      {value: null, locale: 'de-DE'},
    ]);
  });

  it('holds a raw number in a native number input without a locale', async () => {
    const {input, locale, updates} = await mountNumber(
      {inputType: 'number', step: 0.5},
      '1234.5'
    );

    expect(input.type).toBe('number');
    expect(input.name).toBe('fields[weight]');
    expect(input.value).toBe('1234.5');
    expect(locale).toBeNull();

    input.value = '12';
    input.dispatchEvent(new InputEvent('input', {bubbles: true}));

    expect(updates).toEqual(['12']);
  });
});
