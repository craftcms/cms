import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import TextareaControl from './TextareaControl.vue';

describe('TextareaControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  it('connects a text expander to the native textarea', async () => {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(TextareaControl, {
          control: {
            type: 'CraftCms\\Cms\\Form\\Controls\\Textarea',
            component: 'craft:textarea',
            props: {
              textExpanderTriggers: [
                {
                  trigger: '@',
                  boundary: 'whitespace',
                  options: [{label: 'Ada Lovelace', value: '@ada'}],
                },
              ],
            },
            path: ['settings', 'notes'],
            mode: 'editable',
            deltaGroup: ['settings', 'notes'],
          },
          value: '',
          editable: true,
          invalid: false,
          required: false,
          slot: 'input',
        }),
    });
    app.mount(container);
    await nextTick();

    const textarea = container.querySelector('textarea')!;
    const textExpander = container.querySelector('craft-text-expander')!;

    expect(container.querySelector('craft-textarea')?.slot).toBe('input');
    expect(textExpander.for).toBe(textarea.id);
    expect(textExpander.slot).toBe('input');

    textarea.focus();
    textarea.value = '@love';
    textarea.setSelectionRange(5, 5);
    textarea.dispatchEvent(new InputEvent('input', {bubbles: true}));

    expect(container.querySelector('craft-option')?.textContent).toContain(
      'Ada Lovelace'
    );
  });
});
