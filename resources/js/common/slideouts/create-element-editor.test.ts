import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type {OpenSlideoutOptions} from './types';

const openSlideout = vi.fn();
const closeSlideout = vi.fn();

vi.mock('./store', () => ({openSlideout, closeSlideout}));

const {createElementEditor} = await import('./create-element-editor');

function lastOptions(): OpenSlideoutOptions {
  return openSlideout.mock.lastCall![1];
}

describe('createElementEditor', () => {
  beforeEach(() => {
    vi.stubGlobal('Craft', {actionUrl: '/actions'});
    openSlideout.mockResolvedValue({id: 'slideout-1'});
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.clearAllMocks();
  });

  it('opens the element’s editor in a Vue slideout', async () => {
    const chip = document.createElement('div');
    chip.className = 'element';
    Object.assign(chip.dataset, {id: '83', siteId: '2', draftId: '5'});

    const editor = createElementEditor('Entry', chip, {
      params: {fresh: 1},
    });
    await editor.opened;

    const url = new URL(openSlideout.mock.lastCall![0], 'https://x');
    expect(url.pathname).toBe('/actions/elements/edit');
    expect(Object.fromEntries(url.searchParams)).toEqual({
      elementType: 'Entry',
      elementId: '83',
      draftId: '5',
      siteId: '2',
      fresh: '1',
    });
    expect(lastOptions().opener).toBe(chip);
  });

  it('reports a save as the legacy submit event', async () => {
    const onSubmit = vi.fn();
    const onLoad = vi.fn();
    const editor = createElementEditor('Entry', {
      elementId: 83,
      onSubmit,
      onLoad,
    });
    const submitted = vi.fn();
    editor.on('submit', submitted);
    await editor.opened;

    lastOptions().onSaved!({
      data: {modelName: 'element', element: {id: 83, title: 'Houses'}} as any,
    });

    expect(onLoad).toHaveBeenCalledOnce();
    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({data: {id: 83, title: 'Houses'}})
    );
    expect(submitted).toHaveBeenCalledWith(
      expect.objectContaining({data: {id: 83, title: 'Houses'}})
    );
  });

  it('reports autosaved drafts through elementEditor’s update event', async () => {
    const editor = createElementEditor('Entry', {elementId: 83});
    const updated = vi.fn();
    const submitted = vi.fn();
    editor.elementEditor.on('update', updated);
    editor.on('submit', submitted);
    await editor.opened;

    lastOptions().onSaved!({draft: true, data: {} as any});

    expect(updated).toHaveBeenCalledOnce();
    expect(submitted).not.toHaveBeenCalled();
  });

  it('closes its panel', async () => {
    const editor = createElementEditor('Entry', {elementId: 83});
    await editor.opened;

    editor.close();

    expect(closeSlideout).toHaveBeenCalledWith('slideout-1');
  });
});
