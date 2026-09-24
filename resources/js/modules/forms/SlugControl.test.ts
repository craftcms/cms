import {createApp, h, nextTick, reactive} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftInput from '@craftcms/ui/components/input/input';
import SlugControl from './SlugControl.vue';
import type {FormControlPayload, TextControlProps} from './types';

type SlugControlProps = TextControlProps & {
  source?: string[];
  charMap?: Record<string, string>;
  autoGenerate?: boolean;
};

describe('SlugControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
    vi.unstubAllGlobals();
  });

  it('applies inherited text input behavior', async () => {
    const control = reactive<FormControlPayload<SlugControlProps>>({
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

  it('confirms before regenerating an established slug', async () => {
    const confirm = vi.fn();
    const update = vi.fn();
    vi.stubGlobal('confirm', confirm);
    vi.stubGlobal('Craft', {
      allowUppercaseInSlug: false,
      limitAutoSlugsToAscii: true,
      slugWordSeparator: '-',
    });
    const control = reactive<FormControlPayload<SlugControlProps>>({
      type: 'CraftCms\\Cms\\Form\\Controls\\Slug',
      component: 'craft:slug',
      props: {source: ['title'], autoGenerate: false},
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
          value: 'established-slug',
          values: {title: 'Updated title', slug: 'established-slug'},
          editable: true,
          invalid: false,
          required: false,
          'onUpdate:value': update,
        }),
    });
    app.mount(container);
    await nextTick();

    const button = container.querySelector('craft-button')!;
    expect(button.getAttribute('aria-label')).toBe('Regenerate slug');

    confirm.mockReturnValueOnce(false);
    button.click();
    expect(update).not.toHaveBeenCalled();

    confirm.mockReturnValueOnce(true);
    button.click();
    expect(update).toHaveBeenCalledWith('updated-title', 'discrete');
  });

  it('offers regeneration when auto-generation is disabled after saving', async () => {
    const control = reactive<FormControlPayload<SlugControlProps>>({
      type: 'CraftCms\\Cms\\Form\\Controls\\Slug',
      component: 'craft:slug',
      props: {source: ['title']},
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
          value: 'new-entry',
          values: {title: 'New entry', slug: 'new-entry'},
          editable: true,
          invalid: false,
          required: false,
        }),
    });
    app.mount(container);
    await nextTick();

    expect(container.querySelector('craft-button')).toBeNull();

    control.props.autoGenerate = false;
    await nextTick();

    expect(
      container.querySelector('craft-button')?.getAttribute('aria-label')
    ).toBe('Regenerate slug');
  });
});
