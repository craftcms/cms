import {createApp, h, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftInput from '@craftcms/ui/components/input/input';
import TextControl from './TextControl.vue';
import type {TextControlProps, UiControlPayload} from './types';

describe('TextControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  beforeEach(() => {
    window.Craft = {autofocusPreferred: true} as never;
  });

  afterEach(() => {
    app?.unmount();
    container?.remove();
    delete (window as {Craft?: unknown}).Craft;
  });

  it('applies native text input behavior', async () => {
    const control = reactive<UiControlPayload>({
      type: 'CraftCms\\Cms\\Ui\\Controls\\Text',
      component: 'craft:text',
      props: {
        autofocus: true,
        autocomplete: false,
        autocorrect: false,
        autocapitalize: false,
        size: 12,
        dir: 'rtl',
        textExpanderTriggers: [
          {
            trigger: '@',
            boundary: 'whitespace',
            options: [{label: 'Ada Lovelace', value: '@ada'}],
          },
        ],
      },
      path: ['settings', 'name'],
      mode: 'editable',
      deltaGroup: ['settings', 'name'],
    });
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(TextControl, {
          control,
          value: 'Craft',
          editable: true,
          invalid: false,
          required: false,
          slot: 'input',
          'data-ui-control-path': '["settings","name"]',
        }),
    });
    app.mount(container);
    await nextTick();

    const input = container.querySelector<CraftInput>('craft-input')!;
    await input.updateComplete;
    const nativeInput = input.querySelector<HTMLInputElement>('input')!;
    const textExpander = container.querySelector('craft-text-expander')!;

    expect(input.slot).toBe('input');
    expect(input.dataset.uiControlPath).toBe('["settings","name"]');
    expect(textExpander.for).toBe(nativeInput.id);
    expect(textExpander.slot).toBe('input');
    expect(input.autofocus).toBe(true);
    expect(input.autocomplete).toBe('off');
    expect(input.inputSize).toBe(12);
    expect(input.dir).toBe('rtl');
    expect(nativeInput.autofocus).toBe(true);
    expect(nativeInput.autocomplete).toBe('off');
    expect(nativeInput.getAttribute('autocorrect')).toBe('off');
    expect(nativeInput.getAttribute('autocapitalize')).toBe('none');
    expect(nativeInput.size).toBe(12);

    nativeInput.focus();
    nativeInput.value = '@love';
    nativeInput.setSelectionRange(5, 5);
    nativeInput.dispatchEvent(new InputEvent('input', {bubbles: true}));

    expect(container.querySelector('craft-option')?.textContent).toContain(
      'Ada Lovelace'
    );

    control.props.autocorrect = true;
    control.props.autocapitalize = true;
    await nextTick();
    await input.updateComplete;

    expect(nativeInput.hasAttribute('autocorrect')).toBe(false);
    expect(nativeInput.hasAttribute('autocapitalize')).toBe(false);
  });

  async function mountText(
    props: TextControlProps,
    suffix?: () => ReturnType<typeof h>
  ): Promise<HTMLInputElement> {
    const control = reactive<UiControlPayload<TextControlProps>>({
      type: 'CraftCms\\Cms\\Ui\\Controls\\Text',
      component: 'craft:text',
      props,
      path: ['quantity'],
      mode: 'editable',
      deltaGroup: ['quantity'],
    });
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(
          TextControl,
          {
            control,
            value: '42',
            editable: true,
            invalid: false,
            required: false,
          },
          suffix ? {suffix} : undefined
        ),
    });
    app.mount(container);
    await nextTick();

    const input = container.querySelector<CraftInput>('craft-input')!;
    await input.updateComplete;
    // Autofocus waits a frame for anything opening the control, such as a modal, to settle.
    await new Promise((resolve) => requestAnimationFrame(resolve));
    await input.updateComplete;

    return input.querySelector<HTMLInputElement>('input')!;
  }

  it('focuses an autofocused control rendered after page load, without selecting its value', async () => {
    const nativeInput = await mountText({autofocus: true});

    expect(document.activeElement).toBe(nativeInput);
    expect(nativeInput.selectionStart).toBe(nativeInput.selectionEnd);
  });

  it("doesn't autofocus when the user has disabled autofocus", async () => {
    window.Craft.autofocusPreferred = false;
    const nativeInput = await mountText({autofocus: true});

    expect(document.activeElement).not.toBe(nativeInput);
    expect(nativeInput.autofocus).toBe(false);
  });

  it("doesn't select the value when a suffix button is focused", async () => {
    const nativeInput = await mountText({selectOnFocus: true}, () =>
      h('button', {slot: 'suffix', type: 'button'}, 'Regenerate')
    );
    const button = nativeInput
      .closest('craft-input')!
      .querySelector<HTMLButtonElement>('button[slot="suffix"]')!;
    nativeInput.setSelectionRange(1, 1);

    button.focus();

    expect(document.activeElement).toBe(button);
    expect(nativeInput.selectionStart).toBe(1);
    expect(nativeInput.selectionEnd).toBe(1);
  });

  it('selects the value when a selectOnFocus control is focused', async () => {
    const nativeInput = await mountText({selectOnFocus: true});

    expect(document.activeElement).not.toBe(nativeInput);

    nativeInput.focus();

    expect(nativeInput.selectionStart).toBe(0);
    expect(nativeInput.selectionEnd).toBe(nativeInput.value.length);
  });
});
