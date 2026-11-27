import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import type {
  LinkAdvancedField,
  LinkTypeConfig,
} from '../link-field/craft-link-field';
import LinkControl from './LinkControl.vue';
import type {UiControlPayload} from './types';
import {provideUiPreview} from './uiPreview';

describe('LinkControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  it('marks its link field as a preview in a preview', async () => {
    const control: UiControlPayload<{
      types: LinkTypeConfig[];
      showLabelField: boolean;
      advancedFields: LinkAdvancedField[];
      compact: boolean;
    }> = {
      type: 'CraftCms\\Cms\\Ui\\Controls\\Link',
      component: 'craft:link',
      props: {
        types: [{id: 'url', label: 'URL', kind: 'text'}],
        showLabelField: true,
        advancedFields: [],
        compact: true,
      },
      path: ['fields', 'link'],
      mode: 'editable',
      deltaGroup: ['fields', 'link'],
    };
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      setup: () => provideUiPreview(),
      render: () =>
        h(LinkControl, {
          control,
          value: {type: 'url', value: 'https://craftcms.com/docs'},
          editable: true,
        }),
    });
    app.mount(container);
    await nextTick();

    const linkField = document.querySelector('craft-link-field')!;

    expect(linkField.previewing).toBe(true);
  });
});
