import $ from 'jquery';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {CpModal} from './cp-modal';

type Dialog = HTMLElement & {opened: boolean; label: string};

const sendActionRequest = vi.fn();

function dialog(): Dialog {
  return document.body.querySelector('craft-dialog') as Dialog;
}

function button(label: string): HTMLElement {
  return [...dialog().querySelectorAll<HTMLElement>('craft-button')].find(
    (btn) => btn.textContent === label
  )!;
}

async function loaded(): Promise<void> {
  await vi.waitFor(() => {
    expect(dialog().querySelector('input[name="ns[newOwner]"]')).not.toBeNull();
  });
  await new Promise((resolve) => requestAnimationFrame(resolve));
}

describe('CpModal', () => {
  beforeEach(() => {
    vi.stubGlobal('$', $);
    vi.stubGlobal('Craft', {
      t: (_category: string, message: string) => message,
      cp: {
        displayError: vi.fn(),
        displaySuccess: vi.fn(),
        elementThumbLoader: {load: vi.fn()},
      },
      sendActionRequest,
      setElementAttributes: vi.fn(),
      appendHeadHtml: vi.fn(),
      appendBodyHtml: vi.fn(),
      initUiElements: vi.fn(),
      setFocusWithin: vi.fn(),
      findDeltaData: (_initial: string, current: string) => current,
      refreshComponentInstances: vi.fn(),
    });
    sendActionRequest.mockImplementation(async (method: string) =>
      method === 'GET'
        ? {
            data: {
              namespace: 'ns',
              submitButtonLabel: 'Reassign',
              content: '<input name="ns[newOwner]" value="1">',
            },
          }
        : {data: {message: 'Entries reassigned.'}}
    );
  });

  afterEach(() => {
    document.body.replaceChildren();
    vi.unstubAllGlobals();
    vi.clearAllMocks();
  });

  it('loads the screen into a titled dialog, and submits it', async () => {
    const onSubmit = vi.fn();
    new CpModal('entries/reassign-modal', {
      title: 'Reassign entries',
      params: {oldUserIds: [5]},
      onSubmit,
    });
    await loaded();

    expect(dialog().label).toBe('Reassign entries');
    expect(sendActionRequest).toHaveBeenCalledWith(
      'GET',
      'entries/reassign-modal',
      expect.objectContaining({params: {oldUserIds: [5]}})
    );

    const save = button('Reassign');
    expect(save.getAttribute('slot')).toBe('footer');
    save.click();
    await vi.waitFor(() => expect(onSubmit).toHaveBeenCalled());

    expect(sendActionRequest).toHaveBeenLastCalledWith('POST', null, {
      data: 'ns%5BnewOwner%5D=1',
      headers: {'X-Craft-Namespace': 'ns'},
    });
    expect(dialog().opened).toBe(false);
  });

  it('asks before a dismissal discards changes', async () => {
    const onCancel = vi.fn();
    const confirm = vi.fn(() => false);
    vi.stubGlobal('confirm', confirm);
    new CpModal('entries/reassign-modal', {onCancel});
    await loaded();

    dialog().querySelector('input')!.value = '2';
    const dismissal = new Event('craft-before-hide', {cancelable: true});
    dialog().dispatchEvent(dismissal);

    expect(dismissal.defaultPrevented).toBe(true);
    expect(confirm).toHaveBeenCalled();
    expect(dialog().opened).toBe(true);

    confirm.mockReturnValue(true);
    button('Cancel').click();

    expect(dialog().opened).toBe(false);
    expect(onCancel).toHaveBeenCalled();
  });
});
