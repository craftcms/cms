import {ref} from 'vue';
import {beforeEach, describe, expect, it, vi} from 'vitest';
import {useElementIndexViewMode} from './useElementIndexViewMode';
import type {ViewState} from '@/modules/elements/types/view-state';

const merge = vi.fn();
const visitor = {merge, visit: vi.fn(), currentQuery: () => ({})};

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
    const {mode} = useElementIndexViewMode(viewState, visitor);

    mode.value = 'structure';

    expect(viewState.value.mode).toBe('structure');
    expect(merge).toHaveBeenCalledWith(
      {viewMode: 'structure'},
      expect.objectContaining({
        only: ['data', 'pagination', 'structure', 'viewState'],
      })
    );
  });

  it('asks for the structure payload when restoring a persisted mode', () => {
    const {restore} = useElementIndexViewMode(
      viewStateRef('structure'),
      visitor
    );

    expect(restore()).toEqual({
      params: {viewMode: 'structure'},
      only: ['data', 'pagination', 'structure', 'viewState'],
    });
  });

  // Rows rendered for one mode don't draw in another: cards over table rows
  // came out as empty shells.
  it('follows the mode the URL asked for, over the persisted one', () => {
    const viewState = viewStateRef('cards');
    const {restore} = useElementIndexViewMode(
      viewState,
      {...visitor, currentQuery: () => ({viewMode: 'table'})},
      () => 'table'
    );

    expect(restore()).toBeNull();
    expect(viewState.value.mode).toBe('table');
  });

  it('re-requests the persisted mode when the source renders another by default', () => {
    const {restore} = useElementIndexViewMode(
      viewStateRef('table'),
      visitor,
      () => 'cards'
    );

    expect(restore()).toEqual({
      params: {viewMode: 'table'},
      only: ['data', 'pagination', 'structure', 'viewState'],
    });
  });

  it('leaves a payload already rendered for the persisted mode alone', () => {
    const {restore} = useElementIndexViewMode(
      viewStateRef('cards'),
      visitor,
      () => 'cards'
    );

    expect(restore()).toBeNull();
  });

  it('stays put when the mode is unchanged', () => {
    const {mode} = useElementIndexViewMode(viewStateRef('structure'), visitor);

    mode.value = 'structure';

    expect(merge).not.toHaveBeenCalled();
  });
});
