import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import '@/modules/messages/components/cp-messages';
import {
  clearCopiedFieldValue,
  copyFieldValue,
  type CopiedFieldValue,
} from './copiedFieldValue';
import {registerValuePreview, renderValuePreview} from './valuePreviews';

vi.mock('@/common/craft/icon-svg', () => ({iconSvg: async () => null}));

function copied(overrides: Partial<CopiedFieldValue>): CopiedFieldValue {
  return {
    value: null,
    component: 'craft:text',
    label: 'Field',
    text: '',
    ...overrides,
  };
}

describe('renderValuePreview', () => {
  const post = vi.spyOn(actionClient, 'post');

  afterEach(() => post.mockReset());

  it('draws relations as the chips of the elements that still exist', async () => {
    post.mockResolvedValue({
      data: {
        elements: {
          7: ['<div class="chip" data-id="7" data-site-id="1">Apples</div>'],
        },
      },
    } as never);

    const preview = (await renderValuePreview(
      copied({
        component: 'craft:element-select',
        value: [7, 9],
        props: {
          elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
          elements: [
            {id: 7, siteId: 1, label: 'Apples'},
            {id: 9, siteId: 1, label: 'Pears'},
          ],
        },
      })
    )) as HTMLElement;

    expect(post).toHaveBeenCalledWith('app/render-elements', {
      elements: [7, 9].map((id) =>
        expect.objectContaining({
          type: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
          id,
          siteId: 1,
        })
      ),
    });
    expect(
      [...preview.querySelectorAll('.copied-element-chip')].map(
        (chip) => chip.textContent
      )
    ).toEqual(['Apples']);
  });

  it('draws a table’s first rows, without its headings', async () => {
    const rows = Array.from(
      {length: 7},
      (_, row) => `<tr><td>Row ${row + 1}</td></tr>`
    ).join('');
    const preview = (await renderValuePreview(
      copied({
        component: 'craft:table',
        html: `<table><thead><tr><th>Item</th></tr></thead><tbody>${rows}</tbody></table>`,
      })
    )) as HTMLTableElement;

    expect(preview.querySelector('thead')).toBeNull();
    expect(
      [...preview.querySelectorAll('tbody > tr')].map((row) => row.textContent)
    ).toEqual(['Row 1', 'Row 2', 'Row 3', 'Row 4', 'Row 5']);
  });

  it('shows the text instead when a preview can’t be drawn', async () => {
    registerValuePreview('craft:test-broken', () => {
      throw new Error('Nope');
    });
    post.mockRejectedValue(new Error('Offline'));

    expect(
      await renderValuePreview(copied({component: 'craft:test-broken'}))
    ).toBeNull();
    expect(
      await renderValuePreview(
        copied({
          component: 'craft:element-select',
          props: {elementType: 'Entry', elements: [{id: 7, siteId: 1}]},
        })
      )
    ).toBeNull();
    expect(await renderValuePreview(copied({component: 'craft:text'}))).toBe(
      null
    );
  });

  it('draws a list, such as selected options, as a pill for each item', async () => {
    const preview = (await renderValuePreview(
      copied({
        component: 'craft:choice',
        text: 'Red, green, Blue',
        items: ['Red, green', 'Blue'],
      })
    )) as HTMLElement;

    expect(
      [...preview.querySelectorAll('li')].map((li) => li.textContent)
    ).toEqual(['Red, green', 'Blue']);
    expect(
      await renderValuePreview(copied({component: 'craft:choice', items: []}))
    ).toBeNull();
  });

  it('lets a component register its own preview', async () => {
    const node = document.createElement('span');
    registerValuePreview('craft:test-preview', ({value}) => {
      node.textContent = value as string;

      return node;
    });

    expect(
      await renderValuePreview(
        copied({component: 'craft:test-preview', value: 'Hi'})
      )
    ).toBe(node);
    expect(node.textContent).toBe('Hi');
  });
});

describe('the copied card', () => {
  afterEach(() => {
    clearCopiedFieldValue();
    vi.unstubAllGlobals();
    document.body.innerHTML = '';
  });

  async function card(value: Partial<CopiedFieldValue>): Promise<HTMLElement> {
    vi.stubGlobal('Craft', {systemUid: 'test', notificationDuration: 0});
    document.body.innerHTML = '<cp-messages></cp-messages>';
    copyFieldValue(copied(value));

    return vi.waitFor(() => {
      const details = document.querySelector<HTMLElement>(
        '.copied-field-value'
      );
      expect(details).not.toBeNull();

      return details!;
    });
  }

  it('shows the preview to look at, and gives screen readers the text', async () => {
    const details = await card({
      component: 'craft:table',
      text: 'Item\nApples',
      html: '<table><tbody><tr><td>Apples</td></tr></tbody></table>',
    });
    const preview = details.querySelector<HTMLElement>(
      '.copied-field-value-preview'
    )!;

    expect(preview.inert).toBe(true);
    expect(preview.querySelector('table')?.textContent).toBe('Apples');
    expect(details.querySelector('.sr-only')?.textContent).toBe('Item\nApples');
  });

  it('shows the text when there’s no preview', async () => {
    const details = await card({component: 'craft:text', text: 'Hello'});

    expect(details.querySelector('.copied-field-value-preview')).toBeNull();
    expect(details.textContent).toBe('Hello');
  });
});
