import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import type {
  LinkAdvancedField,
  LinkFieldValue,
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

  async function mountLinkControl(
    value: Partial<LinkFieldValue> & Pick<LinkFieldValue, 'type' | 'value'>,
    registeredTypes: string[] | null = null,
    preview = false,
    types: LinkTypeConfig[] = [{id: 'url', label: 'URL', kind: 'text'}]
  ) {
    const updates: Array<Record<string, unknown>> = [];
    const kinds: string[] = [];
    const control: UiControlPayload<{
      types: LinkTypeConfig[];
      showLabelField: boolean;
      advancedFields: LinkAdvancedField[];
      compact: boolean;
      registeredTypes: string[] | null;
    }> = {
      type: 'CraftCms\\Cms\\Ui\\Controls\\Link',
      component: 'craft:link',
      props: {
        types,
        showLabelField: true,
        advancedFields: [],
        compact: true,
        registeredTypes,
      },
      path: ['fields', 'link'],
      mode: 'editable',
      deltaGroup: ['fields', 'link'],
    };
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      setup: () => {
        if (preview) {
          provideUiPreview();
        }
      },
      render: () =>
        h(LinkControl, {
          control,
          value,
          editable: true,
          label: 'Link, row 1',
          'onUpdate:value': (value: Record<string, unknown>, kind: string) => {
            updates.push(value);
            kinds.push(kind);
          },
        }),
    });
    app.mount(container);
    await nextTick();
    await nextTick();

    const linkField = document.querySelector('craft-link-field')!;
    await (linkField as unknown as {updateComplete: Promise<unknown>})
      .updateComplete;

    return {linkField, updates, kinds};
  }

  it('marks its link field as a preview in a preview', async () => {
    const {linkField} = await mountLinkControl(
      {type: 'url', value: 'https://craftcms.com/docs'},
      null,
      true
    );

    expect(linkField.previewing).toBe(true);
  });
});
