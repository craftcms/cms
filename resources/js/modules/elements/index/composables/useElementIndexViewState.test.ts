import {effectScope, nextTick} from 'vue';
import {afterEach, expect, it} from 'vitest';
import {prefixedKey} from '@/common/composables/useStorage';
import {useElementIndexViewState} from './useElementIndexViewState';

const context = {
  elementType: 'craft\\elements\\Entry',
  context: 'index',
  viewState: {},
};
const storageKey = prefixedKey('elementindex.craft\\elements\\Entry.index');

afterEach(() => {
  localStorage.clear();
});

/**
 * The rows on screen were rendered for this tab's view state, so another
 * tab's mode, sort, or column change can't apply here without new rows —
 * table rows have no card HTML, so following it would empty the view.
 */
it('ignores view state changes made in another tab', async () => {
  const scope = effectScope();
  const viewState = scope.run(() => useElementIndexViewState(context))!;
  await nextTick();

  const otherTab = JSON.stringify({
    ...viewState.value,
    sources: {'section:a': {mode: 'cards'}},
  });
  localStorage.setItem(storageKey, otherTab);
  window.dispatchEvent(
    new StorageEvent('storage', {
      key: storageKey,
      newValue: otherTab,
      storageArea: localStorage,
    })
  );
  await nextTick();

  expect(viewState.value.sources).toBeUndefined();
  scope.stop();
});
