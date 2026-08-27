import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import DataTable from './DataTable.vue';
import {createSampleTable} from '@/modules/elements/fixtures/elements';

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({props: {readOnly: false}}),
}));

describe('DataTable', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  it('moves focus to the spinner while re-sorting reloads, then back to the same sort button', async () => {
    const table = createSampleTable();
    const loading = ref(false);

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () => h(DataTable, {table, loading: loading.value}),
    });
    app.mount(container);
    await nextTick();

    const sortButton = container.querySelector<HTMLButtonElement>(
      '#header-title button'
    )!;
    sortButton.focus();
    sortButton.click();
    expect(document.activeElement).toBe(sortButton);

    // The reload starts: `loading` swaps the whole table out for a spinner,
    // tearing the pressed button down with it. Focus should land on the
    // spinner (craft-spinner forwards `.focus()` to its own internal
    // tabindex="-1" wrapper) rather than falling back to <body>.
    loading.value = true;
    await nextTick();
    await nextTick();

    const spinner = container.querySelector('craft-spinner');
    expect(spinner).not.toBeNull();
    expect(document.activeElement).toBe(spinner);
    // Its slotted text is the spinner's accessible name, so a screen reader
    // announces something (rather than going silent) when focus lands on it.
    expect(spinner?.textContent?.trim()).toBe('Sorting');

    // The reload finishes: the table remounts with the new data, and focus
    // returns to the same column's sort button.
    loading.value = false;
    await nextTick();
    await nextTick();

    const restoredButton = container.querySelector('#header-title button');
    expect(restoredButton).not.toBeNull();
    expect(document.activeElement).toBe(restoredButton);
  });

  it('leaves focus alone when loading toggles without a preceding sort', async () => {
    const table = createSampleTable();
    const loading = ref(false);

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () => h(DataTable, {table, loading: loading.value}),
    });
    app.mount(container);
    await nextTick();

    // No sort click happened, so a loading state driven by something else
    // (a filter, pagination, …) shouldn't move focus onto the spinner.
    loading.value = true;
    await nextTick();
    await nextTick();

    const spinner = container.querySelector('craft-spinner');
    expect(document.activeElement).not.toBe(spinner);
    expect(spinner?.textContent?.trim()).toBe('Loading');
  });
});
