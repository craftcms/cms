import {ref, type Ref} from 'vue';
import {beforeEach, describe, expect, it, vi} from 'vitest';
import {useElementIndexViewMode} from './useElementIndexViewMode';
import type {ViewMode, ViewState} from '@/modules/elements/types/view-state';

const merge = vi.fn();
const visitor = {merge, visit: vi.fn(), currentQuery: () => ({})};

const sourceA = () => 'section:a';

function viewStateRef(mode?: ViewMode['mode']) {
  return ref<ViewState>({
    inlineEditing: false,
    showHeaderColumn: true,
    static: false,
    ...(mode ? {sources: {'section:a': {mode}}} : {}),
  });
}

function persisted(viewState: Ref<ViewState>) {
  return viewState.value.sources?.['section:a']?.mode;
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
    const {mode} = useElementIndexViewMode(viewState, visitor, sourceA);

    mode.value = 'structure';

    expect(persisted(viewState)).toBe('structure');
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
      visitor,
      sourceA
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
      sourceA,
      () => 'table'
    );

    expect(restore()).toBeNull();
    expect(persisted(viewState)).toBe('table');
  });

  it('re-requests the persisted mode when the source renders another by default', () => {
    const {restore} = useElementIndexViewMode(
      viewStateRef('table'),
      visitor,
      sourceA,
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
      sourceA,
      () => 'cards'
    );

    expect(restore()).toBeNull();
  });

  it('stays put when the mode is unchanged', () => {
    const {mode} = useElementIndexViewMode(
      viewStateRef('structure'),
      visitor,
      sourceA
    );

    mode.value = 'structure';

    expect(merge).not.toHaveBeenCalled();
  });

  it('keeps a mode per source, falling back to the one rendered', () => {
    const source = ref('section:a');
    const viewState = viewStateRef();
    const {mode, persistedMode} = useElementIndexViewMode(
      viewState,
      visitor,
      () => source.value,
      () => 'cards'
    );

    mode.value = 'thumbs';

    source.value = 'section:b';
    expect(mode.value).toBe('cards');
    expect(persistedMode()).toBeUndefined();

    source.value = 'section:a';
    expect(mode.value).toBe('thumbs');
  });

  it('leaves a source without a persisted mode at the one rendered', () => {
    const {restore} = useElementIndexViewMode(
      viewStateRef(),
      visitor,
      sourceA,
      () => 'cards'
    );

    expect(restore()).toBeNull();
  });
});
