import {createApp, h, nextTick, reactive} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import type CraftInput from '@craftcms/ui/components/input/input';
import SlugControl from './SlugControl.vue';
import type {FormControlPayload, TextControlProps} from './types';

describe('SlugControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  it('applies inherited text input behavior', async () => {
    const control = reactive<
      FormControlPayload<TextControlProps & {source?: string[]}>
    >({
      type: 'CraftCms\\Cms\\Form\\Controls\\Slug',
      component: 'craft:slug',
      props: {
        maxLength: 64,
        placeholder: 'article-slug',
        autocomplete: 'off',
        autocorrect: false,
        autocapitalize: false,
        dir: 'ltr',
        monospace: true,
      },
      path: ['slug'],
      mode: 'editable',
      deltaGroup: ['slug'],
    });
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(SlugControl, {
          control,
          value: 'article-slug',
          values: {slug: 'article-slug'},
          editable: true,
          invalid: false,
          required: false,
          slot: 'input',
          'data-form-control-path': '["slug"]',
        }),
    });
    app.mount(container);
    await nextTick();

    const input = container.querySelector<CraftInput>('craft-input')!;
    await input.updateComplete;
    const nativeInput = input.querySelector<HTMLInputElement>('input')!;

    expect(input.slot).toBe('input');
    expect(input.dataset.formControlPath).toBe('["slug"]');
    expect(input.dir).toBe('ltr');
    expect(input.monospace).toBe(true);
    expect(nativeInput.maxLength).toBe(64);
    expect(nativeInput.placeholder).toBe('article-slug');
    expect(nativeInput.autocomplete).toBe('off');
    expect(nativeInput.getAttribute('autocorrect')).toBe('off');
    expect(nativeInput.getAttribute('autocapitalize')).toBe('none');
  });
});
