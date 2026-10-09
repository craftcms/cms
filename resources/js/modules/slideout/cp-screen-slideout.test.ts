import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {compatify} from '@craftcms/garnish/compat';
import {CpScreenSlideout} from './cp-screen-slideout';

type OpenOptions = {
  opener?: HTMLElement;
  onSaved: (result: {data?: any; draft?: boolean}) => void;
  onClosed: () => void;
};

const openSlideout = vi.fn();
const closeSlideout = vi.fn();
const refreshComponentInstances = vi.fn();

beforeEach(() => {
  openSlideout.mockResolvedValue({id: 'slideout-1'});

  Object.assign(globalThis, {
    Craft: {
      openSlideout,
      closeSlideout,
      refreshComponentInstances,
      getUrl: (path: string, params: Record<string, unknown>) =>
        `${path}?${new URLSearchParams(params as Record<string, string>)}`,
      getActionUrl: (action: string, params: Record<string, unknown>) =>
        `/actions/${action}?${new URLSearchParams(params as Record<string, string>)}`,
    },
    $: (el: unknown) => (el ? [el] : []),
  });
});

afterEach(() => {
  vi.clearAllMocks();
});

function openOptions(): OpenOptions {
  return openSlideout.mock.calls[0]![1];
}

describe('opening in the Vue slideout stack', () => {
  it('opens an action path as an action URL with its params', () => {
    new CpScreenSlideout('my-plugin/slideout', {params: {id: 5}});

    expect(openSlideout).toHaveBeenCalledWith(
      '/actions/my-plugin/slideout?id=5',
      expect.any(Object)
    );
  });

  it('opens a URL as is', () => {
    new CpScreenSlideout('https://example.test/admin/settings/fields/edit');

    expect(openSlideout.mock.calls[0]![0]).toBe(
      'https://example.test/admin/settings/fields/edit?'
    );
  });

  it('opens from the legacy global too', () => {
    const LegacyCpScreenSlideout = compatify(CpScreenSlideout);

    new LegacyCpScreenSlideout('my-plugin/slideout');

    expect(openSlideout).toHaveBeenCalledTimes(1);
  });

  it('relays a save as a submit event with the saved model', () => {
    const slideout = new CpScreenSlideout('volumes/edit-volume');
    const onSubmit = vi.fn();
    slideout.on('submit', onSubmit);

    const data = {
      modelName: 'volume',
      modelClass: 'Volume',
      modelId: 3,
      volume: {id: 3, name: 'Images'},
    };
    openOptions().onSaved({data});

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        response: {data},
        data: {id: 3, name: 'Images'},
      })
    );
    expect(refreshComponentInstances).toHaveBeenCalledWith('Volume', 3);
  });

  it('ignores autosaved drafts', () => {
    const slideout = new CpScreenSlideout('volumes/edit-volume');
    const onSubmit = vi.fn();
    slideout.on('submit', onSubmit);

    openOptions().onSaved({draft: true, data: {}});

    expect(onSubmit).not.toHaveBeenCalled();
  });

  it('relays the panel closing as a close event', () => {
    const slideout = new CpScreenSlideout('volumes/edit-volume');
    const onClose = vi.fn();
    slideout.on('close', onClose);

    openOptions().onClosed();

    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('closes the Vue panel when closed programmatically', async () => {
    const slideout = new CpScreenSlideout('volumes/edit-volume');
    await openSlideout.mock.results[0]!.value;
    await Promise.resolve();

    slideout.close();
    expect(closeSlideout).toHaveBeenCalledWith('slideout-1', {force: true});

    slideout.closeMeMaybe();
    expect(closeSlideout).toHaveBeenLastCalledWith('slideout-1');
  });

  it('leaves legacy subclasses on the legacy panel', () => {
    const LegacyCpScreenSlideout = compatify(CpScreenSlideout);
    const Subclass = LegacyCpScreenSlideout.extend({
      init(this: any, action: string) {
        try {
          this.base(action);
        } catch {
          // The legacy panel needs a real CP page to build itself.
        }
      },
    });

    new Subclass('my-plugin/slideout');

    expect(openSlideout).not.toHaveBeenCalled();
  });
});
