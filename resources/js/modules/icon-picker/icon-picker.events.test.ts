import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import CraftIconPicker from './icon-picker.ce';

vi.mock('@/common/composables/useAsyncIcon', async () => {
  const {ref} = await import('vue');
  return {useAsyncIcon: () => ({html: ref(null), state: ref('idle')})};
});

if (!customElements.get('craft-icon-picker-events-test')) {
  customElements.define('craft-icon-picker-events-test', CraftIconPicker);
}

describe('craft-icon-picker events', () => {
  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('emits one change per edit, once the posted value has updated', async () => {
    const form = document.createElement('form');
    form.innerHTML =
      '<craft-icon-picker-events-test name="icon" value="star"></craft-icon-picker-events-test>';
    document.body.append(form);
    const picker = form.querySelector('craft-icon-picker-events-test')!;
    await vi.waitFor(() => expect(new FormData(form).get('icon')).toBe('star'));
    const posted: unknown[] = [];
    picker.addEventListener('change', () =>
      posted.push(new FormData(form).get('icon'))
    );

    const remove = [...picker.querySelectorAll('craft-button')].find(
      (button) => button.textContent?.trim() === 'Remove'
    )!;
    remove.click();
    await vi.waitFor(() => expect(posted).not.toEqual([]));
    await new Promise((resolve) => setTimeout(resolve, 50));

    expect(posted).toEqual(['']);
  });
});
