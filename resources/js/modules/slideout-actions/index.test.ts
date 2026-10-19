import {afterEach, describe, expect, it, vi} from 'vite-plus/test';

const openSlideout = vi.hoisted(() => vi.fn());
vi.mock('@/common/slideouts', () => ({openSlideout}));

await import('./index');

const entryType = 'CraftCms\\Cms\\Entry\\Elements\\Entry';

afterEach(() => {
  openSlideout.mockReset();
  vi.restoreAllMocks();
});

function dispatch(name: string, detail: Record<string, unknown>): void {
  window.dispatchEvent(new CustomEvent(name, {detail}));
}

function openedUrl(): URL {
  return new URL(openSlideout.mock.calls[0]![0], 'https://example.test');
}

describe('craft:open-slideout', () => {
  it('opens the URL with the trigger as opener', () => {
    const trigger = document.createElement('button');

    dispatch('craft:open-slideout', {
      url: '/admin/settings/sections/2',
      trigger,
    });

    expect(openSlideout).toHaveBeenCalledWith('/admin/settings/sections/2', {
      opener: trigger,
    });
  });

  it('ignores an event without a URL', () => {
    dispatch('craft:open-slideout', {});

    expect(openSlideout).not.toHaveBeenCalled();
  });
});

describe('craft:edit-element', () => {
  it('opens the element editor with the element’s params', () => {
    dispatch('craft:edit-element', {
      elementType: entryType,
      settings: {elementId: 22, siteId: 1, draftId: null, ownerId: null},
    });

    const url = openedUrl();
    expect(url.pathname).toMatch(/\/elements\/edit$/);
    expect(Object.fromEntries(url.searchParams)).toEqual({
      elementType: entryType,
      elementId: '22',
      siteId: '1',
    });
  });

  it('drops a draft ID the trigger no longer carries', () => {
    const trigger = document.createElement('div');
    document.body.append(trigger);

    dispatch('craft:edit-element', {
      elementType: entryType,
      settings: {elementId: 22, draftId: 5},
      trigger,
    });

    expect(openedUrl().searchParams.has('draftId')).toBe(false);
    trigger.remove();
  });

  it('opens the edit page in a new window on ctrl-click', () => {
    const open = vi.spyOn(window, 'open').mockReturnValue(null);

    dispatch('craft:edit-element', {
      elementType: entryType,
      settings: {elementId: 22},
      cpEditUrl: '/admin/content/entries/pages/22-about',
      sourceEvent: new MouseEvent('click', {ctrlKey: true, metaKey: true}),
    });

    expect(open).toHaveBeenCalledWith('/admin/content/entries/pages/22-about');
    expect(openSlideout).not.toHaveBeenCalled();
  });

  it('re-announces a finished save, but not an autosaved draft', () => {
    const saved = vi.fn();
    window.addEventListener('craft:element-saved', saved);

    dispatch('craft:edit-element', {
      elementType: entryType,
      settings: {elementId: 22},
    });
    const {onSaved} = openSlideout.mock.calls[0]![1];
    onSaved({draft: true, data: {element: {id: 22}}});
    onSaved({data: {element: {id: 22}}});

    expect(saved).toHaveBeenCalledOnce();
    expect((saved.mock.calls[0]![0] as CustomEvent).detail).toEqual({
      element: {id: 22},
    });
    window.removeEventListener('craft:element-saved', saved);
  });
});
