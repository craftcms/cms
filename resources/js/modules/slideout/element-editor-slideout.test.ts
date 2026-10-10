import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {compatify} from '@craftcms/garnish/compat';
import {ElementEditorSlideout} from './element-editor-slideout';

type OpenOptions = {
  onSaved: (result: {data?: any; draft?: boolean}) => void;
  onClosed: () => void;
};

const openSlideout = vi.fn();
const entryType = 'CraftCms\\Cms\\Entry\\Elements\\Entry';

beforeEach(() => {
  openSlideout.mockResolvedValue({id: 'slideout-1'});

  const $ = (el: unknown) =>
    Object.assign(el ? [el] : [], {
      parents: () => [],
      data: () => undefined,
    });

  Object.assign(globalThis, {
    Craft: {
      openSlideout,
      refreshComponentInstances: vi.fn(),
      getActionUrl: (action: string, params: Record<string, unknown>) =>
        `/actions/${action}?${new URLSearchParams(params as Record<string, string>)}`,
    },
    $,
  });
});

afterEach(() => {
  vi.clearAllMocks();
});

function openOptions(): OpenOptions {
  return openSlideout.mock.calls[0]![1];
}

describe('opening in the Vue slideout stack', () => {
  it('opens the element edit action with the element’s params', () => {
    new ElementEditorSlideout(null, {
      elementType: entryType,
      elementId: 6,
      siteId: 1,
    });

    expect(openSlideout.mock.calls[0]![0]).toBe(
      `/actions/elements/edit?${new URLSearchParams({
        elementType: entryType,
        elementId: '6',
        siteId: '1',
      })}`
    );
  });

  it('opens from the legacy global too', () => {
    const LegacyElementEditorSlideout = compatify(ElementEditorSlideout);

    new LegacyElementEditorSlideout(null, {
      elementType: entryType,
      elementId: 6,
    });

    expect(openSlideout).toHaveBeenCalledTimes(1);
  });

  it('relays a save to submit and onSaveElement', () => {
    const onSaveElement = vi.fn();
    const slideout = new ElementEditorSlideout(null, {
      elementType: entryType,
      elementId: 6,
      onSaveElement,
    });
    const onSubmit = vi.fn();
    slideout.on('submit', onSubmit);

    const data = {modelName: 'element', element: {id: 6, title: 'Hello'}};
    openOptions().onSaved({data});

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({response: {data}})
    );
    expect(onSaveElement).toHaveBeenCalledWith({id: 6, title: 'Hello'});
  });

  it('keeps the legacy panel for callers that drive its element editor', () => {
    try {
      new ElementEditorSlideout(null, {
        elementType: entryType,
        elementId: 6,
        onBeforeSubmit: async () => {},
      });
    } catch {
      // The legacy panel needs a real CP page to build itself.
    }

    expect(openSlideout).not.toHaveBeenCalled();
  });
});
