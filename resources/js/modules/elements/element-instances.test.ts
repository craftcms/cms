import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {broadcast} from '@/common/craft/broadcast';
import {
  installElementInstanceRefresh,
  onElementChanged,
  refreshElementInstances,
} from './element-instances';

const ENTRY = 'CraftCms\\Cms\\Entry\\Elements\\Entry';

function chipHtml(
  label: string,
  {
    id = 7,
    siteId = 1,
    className = 'element small',
    attrs = '',
    children = '',
  }: {
    id?: number;
    siteId?: number;
    className?: string;
    attrs?: string;
    children?: string;
  } = {}
): string {
  return `<craft-chip id="chip-${id}-${siteId}" class="${className}" data-type="${ENTRY}" data-id="${id}" data-site-id="${siteId}" data-settings='{"context":"field","ui":"chip"}' ${attrs}><div slot="status"><craft-indicator label="Live"></craft-indicator></div><div id="chip-${id}-${siteId}-label"><div><a href="/admin/entries/${id}">${label}</a></div></div>${children}</craft-chip>`;
}

function respondWith(
  render: (id: number, siteId: number, instance: number) => string
) {
  return vi
    .spyOn(actionClient, 'post')
    .mockImplementation(async (_url, body: any) => {
      const [{id, siteId, instances}] = body.elements;
      const rendered: Record<number, string> = {};
      instances.forEach((_: unknown, i: number) => {
        rendered[i] = render(id, Number(siteId), i);
      });
      return {data: {elements: {[id]: rendered}}} as never;
    });
}

afterEach(() => {
  vi.restoreAllMocks();
  document.body.innerHTML = '';
});

describe('refreshElementInstances()', () => {
  it('re-renders a chip in place, keeping what the page added to it', async () => {
    document.body.innerHTML = chipHtml('Old title', {
      className: 'element small cp-color-red sel',
      attrs: 'selected data-stale="1"',
      children:
        '<div slot="suffix"><button id="actions">Actions</button><craft-reorder-button></craft-reorder-button></div><input type="hidden" name="related[]" value="7">',
    });
    const chip = document.querySelector('craft-chip')!;
    const actions = document.getElementById('actions')!;
    const input = chip.querySelector('input')!;
    const status = chip.querySelector('[slot="status"]')!;
    respondWith(() =>
      chipHtml('New title', {className: 'element small cp-color-blue'})
    );

    await refreshElementInstances(7);

    expect(document.querySelector('craft-chip')).toBe(chip);
    expect(chip.querySelector('a')!.textContent).toBe('New title');
    expect(chip.querySelector('[slot="suffix"] #actions')).toBe(actions);
    expect(chip.querySelector(':scope > input')).toBe(input);
    expect(chip.querySelectorAll('[slot="status"]')).toHaveLength(1);
    expect(chip.querySelector('[slot="status"]')).toBe(status);
    expect(chip.hasAttribute('selected')).toBe(true);
    expect(chip.hasAttribute('data-stale')).toBe(false);
    expect([...chip.classList]).toEqual(
      expect.arrayContaining(['element', 'small', 'sel', 'cp-color-blue'])
    );
    expect(chip.classList.contains('cp-color-red')).toBe(false);
  });

  it('leaves chips without settings, and other elements’ chips, alone', async () => {
    document.body.innerHTML = `
      <craft-chip class="element" data-id="7" data-site-id="1">Vue-owned</craft-chip>
      ${chipHtml('Other', {id: 8})}`;
    const post = vi.spyOn(actionClient, 'post');

    await refreshElementInstances(7);

    expect(post).not.toHaveBeenCalled();
    expect(document.body.textContent).toContain('Vue-owned');
  });

  it('sends one request per site, each with that site’s instances in order', async () => {
    document.body.innerHTML = [
      chipHtml('A', {siteId: 1}),
      chipHtml('B', {siteId: 2}),
      chipHtml('C', {siteId: 1}).replace('chip-7-1', 'chip-7-1b'),
    ].join('');
    const post = respondWith((_id, siteId, instance) =>
      chipHtml(`site ${siteId} #${instance}`, {siteId})
    );

    await refreshElementInstances(7);

    expect(post).toHaveBeenCalledTimes(2);
    const bySite = Object.fromEntries(
      post.mock.calls.map(([url, body]: any) => {
        expect(url).toContain('app/render-elements');
        return [body.elements[0].siteId, body.elements[0].instances.length];
      })
    );
    expect(bySite).toEqual({1: 2, 2: 1});
    expect(
      [...document.querySelectorAll('craft-chip a')].map((a) => a.textContent)
    ).toEqual(['site 1 #0', 'site 2 #0', 'site 1 #1']);
  });

  it('keeps a card’s actions and active state while its title changes', async () => {
    const card = (title: string, actions: string, attrs = '') =>
      `<craft-card id="card-1" class="element card" data-type="${ENTRY}" data-id="7" data-site-id="1" data-settings='{"ui":"card"}' ${attrs}><div class="card-titlebar"><div class="card-titlebar-label">${title}</div><div class="card-actions-container"><div class="card-actions">${actions}</div></div></div><div class="card-main">${title} body</div></craft-card>`;
    document.body.innerHTML = card(
      'Old',
      '<div role="checkbox" class="checkbox"></div><button class="edit-btn">Edit</button>',
      'active'
    );
    const el = document.querySelector('craft-card')!;
    const container = el.querySelector('.card-actions-container')!;
    const titlebar = el.querySelector('.card-titlebar')!;
    respondWith(() => card('New', '<div class="checkbox"></div>'));

    await refreshElementInstances(7);

    expect(el.querySelector('.card-titlebar')).toBe(titlebar);
    expect(el.querySelector('.card-actions-container')).toBe(container);
    expect(el.querySelector('.edit-btn')).not.toBeNull();
    expect(el.querySelector('.card-titlebar-label')!.textContent).toBe('New');
    expect(el.querySelector('.card-main')!.textContent).toBe('New body');
    expect(el.hasAttribute('active')).toBe(true);
  });

  it('keeps focus on kept and unchanged controls, and moves it to a changed one’s replacement', async () => {
    document.body.innerHTML =
      chipHtml('Old', {
        children: '<div slot="suffix"><button id="menu">Actions</button></div>',
      }) + chipHtml('Old', {siteId: 2});
    let title = 'New';
    respondWith((_id, siteId) => chipHtml(title, {siteId}));

    const menu = document.getElementById('menu')!;
    menu.focus();
    await refreshElementInstances(7);
    expect(document.activeElement).toBe(menu);

    const link = document.querySelector<HTMLElement>(
      'craft-chip[data-site-id="2"] a'
    )!;
    link.focus();
    await refreshElementInstances(7);
    expect(document.activeElement).toBe(link);

    title = 'Newer';
    await refreshElementInstances(7);
    const newLink = document.querySelector('craft-chip[data-site-id="2"] a');
    expect(newLink).not.toBe(link);
    await vi.waitFor(() => expect(document.activeElement).toBe(newLink));
  });
});

describe('installElementInstanceRefresh()', () => {
  beforeEach(() => installElementInstanceRefresh());

  it('refreshes chips when an element is saved in this tab', async () => {
    document.body.innerHTML = chipHtml('Old');
    const post = respondWith(() => chipHtml('Saved'));

    broadcast({event: 'saveElement', id: 7});

    await vi.waitFor(() =>
      expect(document.querySelector('craft-chip a')!.textContent).toBe('Saved')
    );
    expect(post).toHaveBeenCalledTimes(1);
  });

  it('refreshes chips for the canonical element when its draft is deleted', async () => {
    document.body.innerHTML = chipHtml('Edited');
    respondWith(() => chipHtml('Canonical'));

    broadcast({event: 'deleteDraft', canonicalId: 7, draftId: 3});

    await vi.waitFor(() =>
      expect(document.querySelector('craft-chip a')!.textContent).toBe(
        'Canonical'
      )
    );
  });

  it('refreshes chips when legacy code asks it to', async () => {
    document.body.innerHTML = chipHtml('Old');
    respondWith(() => chipHtml('Legacy'));

    window.dispatchEvent(
      new CustomEvent('craft:refresh-element-instances', {detail: {id: 7}})
    );

    await vi.waitFor(() =>
      expect(document.querySelector('craft-chip a')!.textContent).toBe('Legacy')
    );
  });
});

describe('onElementChanged()', () => {
  it('only hears this tab’s saves with includeSelf', async () => {
    const otherTabs = vi.fn();
    const everywhere = vi.fn();
    const stops = [
      onElementChanged(otherTabs),
      onElementChanged(everywhere, {includeSelf: true}),
    ];

    broadcast({event: 'saveElement', id: '12'});
    broadcast({event: 'deleteDraft', canonicalId: 5, draftId: 9});

    await vi.waitFor(() => expect(everywhere).toHaveBeenCalledTimes(2));
    stops.forEach((stop) => stop());
    expect(otherTabs).not.toHaveBeenCalled();
    expect(everywhere.mock.calls).toEqual(expect.arrayContaining([[12], [5]]));
  });

  it('calls once for messages about the same element that arrive together', async () => {
    const handler = vi.fn();
    const stop = onElementChanged(handler, {includeSelf: true});

    broadcast({event: 'saveElement', id: 7});
    broadcast({event: 'deleteDraft', canonicalId: 7, draftId: 3});
    broadcast({event: 'saveElement', id: 7});

    await vi.waitFor(() => expect(handler).toHaveBeenCalled());
    await new Promise((resolve) => setTimeout(resolve, 100));
    stop();
    expect(handler.mock.calls).toEqual([[7]]);
  });
});
