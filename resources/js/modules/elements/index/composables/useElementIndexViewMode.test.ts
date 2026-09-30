import {ref} from 'vue';
import {beforeEach, describe, expect, it, vi} from 'vitest';
import {useElementIndexViewMode} from './useElementIndexViewMode';
import type {ViewState} from '@/modules/elements/types/view-state';

const merge = vi.fn();
const visitor = {merge, visit: vi.fn(), currentQuery: () => ({})};
const route = {url: () => '/cp/entries'};

function viewStateRef(mode: ViewState['mode']) {
  return ref<ViewState>({
    inlineEditing: false,
    mode,
    showHeaderColumn: true,
    static: false,
  });
}

describe('useElementIndexViewMode', () => {
  beforeEach(() => {
    merge.mockClear();
    window.history.replaceState({}, '', '/cp/entries');
  });

  // The server only fills in `structure` while the index is ordered by
  // structure, so a switch that left it out would strand the rows without
  // their reordering affordances until the next full page load.
  it('refreshes the structure payload when the mode changes', () => {
    const viewState = viewStateRef('table');
    const {mode} = useElementIndexViewMode(route, viewState, visitor);

    mode.value = 'structure';

    expect(viewState.value.mode).toBe('structure');
    expect(merge).toHaveBeenCalledWith(
      {viewMode: 'structure'},
      expect.objectContaining({only: ['data', 'pagination', 'structure']})
    );
  });

  it('asks for the structure payload when restoring a persisted mode', () => {
    const {restore} = useElementIndexViewMode(
      route,
      viewStateRef('structure'),
      visitor
    );

    expect(restore()).toEqual({
      params: {viewMode: 'structure'},
      only: ['data', 'pagination', 'structure'],
    });
  });

  it('stays put when the mode is unchanged', () => {
    const {mode} = useElementIndexViewMode(
      route,
      viewStateRef('structure'),
      visitor
    );

    mode.value = 'structure';

    expect(merge).not.toHaveBeenCalled();
  });
});
