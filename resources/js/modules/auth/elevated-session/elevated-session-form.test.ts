import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {ElevatedSessionForm} from './elevated-session-form';

describe('ElevatedSessionForm', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('only requires confirmation when a tracked input changes', () => {
    document.body.innerHTML = `
      <form id="settings">
        <input name="name" value="Original">
        <input class="permission" name="permissions[]" value="edit">
      </form>
    `;
    const form = document.querySelector<HTMLFormElement>('#settings');
    if (!form) throw new Error('Expected the settings form fixture.');
    const elevatedForm = new ElevatedSessionForm(form, [
      '[name="name"]',
      '.permission',
    ]);

    expect(elevatedForm.inputsChanged()).toBe(false);
    const name = form.querySelector<HTMLInputElement>('[name="name"]');
    if (!name) throw new Error('Expected the name input fixture.');
    name.value = 'Changed';
    expect(elevatedForm.inputsChanged()).toBe(true);
  });

  it('detects inputs added after initialization', () => {
    document.body.innerHTML = '<form id="settings"></form>';
    const form = document.querySelector<HTMLFormElement>('#settings');
    if (!form) throw new Error('Expected the settings form fixture.');
    const elevatedForm = new ElevatedSessionForm(form, '.permission');

    expect(elevatedForm.inputsChanged()).toBe(false);
    form.insertAdjacentHTML(
      'beforeend',
      '<input class="permission" name="permissions[]" value="edit">'
    );
    expect(elevatedForm.inputsChanged()).toBe(true);
  });

  it('stands down when an earlier listener already canceled the submit', async () => {
    document.body.innerHTML = `
      <form id="settings">
        <input name="name" value="Original">
      </form>
    `;
    const form = document.querySelector<HTMLFormElement>('#settings');
    if (!form) throw new Error('Expected the settings form fixture.');
    form.addEventListener('submit', (event) => event.preventDefault());
    const manager = {require: vi.fn().mockResolvedValue(true)};
    new ElevatedSessionForm(form, '[name="name"]', manager);
    const name = form.querySelector<HTMLInputElement>('[name="name"]');
    if (!name) throw new Error('Expected the name input fixture.');
    name.value = 'Changed';

    form.requestSubmit();
    await Promise.resolve();

    expect(manager.require).not.toHaveBeenCalled();
  });

  it('resubmits with the original submitter after confirmation', async () => {
    document.body.innerHTML = `
      <form id="settings">
        <input name="name" value="Original">
        <button type="submit" name="redirect" value="1">Save</button>
      </form>
    `;
    const form = document.querySelector<HTMLFormElement>('#settings');
    if (!form) throw new Error('Expected the settings form fixture.');
    const button = form.querySelector<HTMLButtonElement>('button');
    if (!button) throw new Error('Expected the submit button fixture.');
    const manager = {require: vi.fn().mockResolvedValue(true)};
    new ElevatedSessionForm(form, '[name="name"]', manager);
    const submitted = vi.fn();
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      if (!(event instanceof SubmitEvent))
        throw new Error('Expected a submit event.');
      submitted(event.submitter);
    });
    const name = form.querySelector<HTMLInputElement>('[name="name"]');
    if (!name) throw new Error('Expected the name input fixture.');
    name.value = 'Changed';

    form.requestSubmit(button);
    await Promise.resolve();
    await Promise.resolve();

    expect(manager.require).toHaveBeenCalledOnce();
    expect(submitted).toHaveBeenCalledOnce();
    expect(submitted).toHaveBeenCalledWith(button);
  });
});
