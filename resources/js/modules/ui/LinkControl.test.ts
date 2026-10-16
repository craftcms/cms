import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import type {
  LinkAdvancedField,
  LinkFieldValue,
  LinkTypeConfig,
} from '../link-field/craft-link-field';
import LinkControl from './LinkControl.vue';
import type {UiControlPayload} from './types';

describe('LinkControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  async function mountLinkControl(
    value: Partial<LinkFieldValue> & Pick<LinkFieldValue, 'type' | 'value'>,
    registeredTypes: string[] | null = null
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
        types: [{id: 'url', label: 'URL', kind: 'text'}],
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

  it('summarizes compact links and applies edits from the popover', async () => {
    const {linkField, updates, kinds} = await mountLinkControl({
      type: 'url',
      value: 'https://craftcms.com/docs',
      label: 'Docs',
    });
    const summary = container!.querySelector('.link-control__summary')!;
    const button = container!.querySelector<HTMLElement>(
      '.link-control__actions > craft-button'
    )!;

    expect(summary.textContent?.replace(/\s+/g, ' ').trim()).toBe(
      'Docs URL: https://craftcms.com/docs'
    );
    expect(button.textContent?.trim()).toBe('Edit link');
    expect(button.getAttribute('aria-label')).toBe('Edit link, Link, row 1');
    expect(button.getAttribute('aria-describedby')).toBe(summary.id);
    expect(linkField.closest('craft-popover')?.getAttribute('for')).toBe(
      button.id
    );
    expect(
      container!.querySelector<HTMLInputElement>(
        'input[name="fields[link][value]"]'
      )?.value
    ).toBe('https://craftcms.com/docs');

    linkField.dispatchEvent(
      new CustomEvent('link-change', {
        bubbles: true,
        detail: {
          kind: 'continuous',
          value: {
            type: 'url',
            value: 'https://craftcms.com/draft',
            href: 'https://craftcms.com/draft',
          },
        },
      })
    );
    linkField.dispatchEvent(
      new CustomEvent('apply', {
        bubbles: true,
        detail: {
          type: 'url',
          value: 'https://craftcms.com',
          label: '',
          href: 'https://craftcms.com',
        },
      })
    );

    expect(updates).toEqual([
      {type: 'url', value: 'https://craftcms.com', label: ''},
    ]);
    expect(kinds).toEqual(['discrete']);
  });

  it('offers to add a link when a compact link is empty', async () => {
    await mountLinkControl({type: 'url', value: ''});

    expect(container!.querySelector('.link-control__summary')).toBeNull();
    expect(
      container!
        .querySelector('.link-control__actions > craft-button')
        ?.textContent?.trim()
    ).toBe('Add link');
  });

  it('removes a compact link', async () => {
    const {updates, kinds} = await mountLinkControl({
      type: 'url',
      value: 'https://craftcms.com',
    });

    [...container!.querySelectorAll<HTMLElement>('craft-button')]
      .find((button) => button.textContent?.trim() === 'Remove link')!
      .click();

    expect(updates).toEqual([{type: 'url', value: ''}]);
    expect(kinds).toEqual(['discrete']);
  });

  it('locks links of unavailable types, and keeps submitting them', async () => {
    await mountLinkControl({type: 'retired', value: 'opaque', label: 'Kept'}, [
      'url',
    ]);
    const button = container!.querySelector<HTMLElement & {disabled: boolean}>(
      '.link-control__actions > craft-button'
    )!;

    expect(button.disabled).toBe(true);
    expect(
      container!.querySelector('.link-control__summary')?.textContent
    ).toContain('This link type is no longer available.');
    expect(
      Object.fromEntries(
        [
          ...container!.querySelectorAll<HTMLInputElement>(
            'input[type="hidden"]'
          ),
        ]
          .filter((input) => input.value !== '')
          .map((input) => [input.name, input.value])
      )
    ).toEqual({
      'fields[link][type]': 'retired',
      'fields[link][value]': 'opaque',
      'fields[link][label]': 'Kept',
    });
  });

  it('keeps the popover in the same top-level container as its cell', async () => {
    const {linkField} = await mountLinkControl({type: 'url', value: ''});

    expect(container!.contains(linkField)).toBe(true);
    expect(
      container!.querySelector('.link-control--compact')!.contains(linkField)
    ).toBe(false);
  });
});
