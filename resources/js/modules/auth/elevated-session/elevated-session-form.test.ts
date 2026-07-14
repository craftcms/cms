import {beforeEach, describe, expect, it, vi} from 'vitest';
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
    const form = document.querySelector<HTMLFormElement>('#settings')!;
    const elevatedForm = new ElevatedSessionForm(form, [
      '[name="name"]',
      '.permission',
    ]);

    expect(elevatedForm.inputsChanged()).toBe(false);
    form.querySelector<HTMLInputElement>('[name="name"]')!.value = 'Changed';
    expect(elevatedForm.inputsChanged()).toBe(true);
  });

  it('detects inputs added after initialization', () => {
    document.body.innerHTML = '<form id="settings"></form>';
    const form = document.querySelector<HTMLFormElement>('#settings')!;
    const elevatedForm = new ElevatedSessionForm(form, '.permission');

    expect(elevatedForm.inputsChanged()).toBe(false);
    form.insertAdjacentHTML(
      'beforeend',
      '<input class="permission" name="permissions[]" value="edit">'
    );
    expect(elevatedForm.inputsChanged()).toBe(true);
  });

  it('resubmits with the original submitter after confirmation', async () => {
    document.body.innerHTML = `
      <form id="settings">
        <input name="name" value="Original">
        <button type="submit" name="redirect" value="1">Save</button>
      </form>
    `;
    const form = document.querySelector<HTMLFormElement>('#settings')!;
    const button = form.querySelector<HTMLButtonElement>('button')!;
    const manager = {require: vi.fn().mockResolvedValue(true)};
    new ElevatedSessionForm(form, '[name="name"]', manager);
    const submitted = vi.fn();
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      submitted((event as SubmitEvent).submitter);
    });
    form.querySelector<HTMLInputElement>('[name="name"]')!.value = 'Changed';

    form.requestSubmit(button);
    await Promise.resolve();
    await Promise.resolve();

    expect(manager.require).toHaveBeenCalledOnce();
    expect(submitted).toHaveBeenCalledOnce();
    expect(submitted).toHaveBeenCalledWith(button);
  });
});
