import {ref} from 'vue';
import {beforeEach, describe, expect, it, vi} from 'vitest';
import {useElementIndexThumbSize} from './useElementIndexThumbSize';
import type {
  SourceViewState,
  ViewMode,
  ViewState,
} from '@/modules/elements/types/view-state';

const merge = vi.fn();
const visitor = {merge, visit: vi.fn(), currentQuery: () => ({})};

function viewStateRef(thumbSizes?: SourceViewState['thumbSizes']) {
  return ref<ViewState>({
    inlineEditing: false,
    showHeaderColumn: true,
    static: false,
    ...(thumbSizes ? {sources: {'volume:a': {thumbSizes}}} : {}),
  });
}

const sourceA = () => 'volume:a';

describe('useElementIndexThumbSize', () => {
  beforeEach(() => {
    merge.mockClear();
  });

  // Tiles have always been card-sized, and table rows small.
  it('defaults tiles to large and table rows to small, each with its own sizes', () => {
    const mode = ref<ViewMode['mode']>('structure');
    const {thumbSize, options} = useElementIndexThumbSize(
      viewStateRef(),
      visitor,
      () => mode.value,
      sourceA
    );

    expect([thumbSize.value, options.value]).toEqual([
      'sm',
      ['sm', 'md', 'lg'],
    ]);

    mode.value = 'thumbs';
    expect([thumbSize.value, options.value]).toEqual([
      'lg',
      ['sm', 'md', 'lg', 'xl', '2xl'],
    ]);

    mode.value = 'cards';
    expect(options.value).toEqual([]);
  });

  it('persists a size for the current view and re-requests its rows', () => {
    const viewState = viewStateRef();
    const {thumbSize} = useElementIndexThumbSize(
      viewState,
      visitor,
      () => 'thumbs',
      sourceA
    );

    thumbSize.value = '2xl';
    expect(viewState.value.sources?.['volume:a']?.thumbSizes).toEqual({
      thumbs: '2xl',
    });
    expect(merge).toHaveBeenLastCalledWith(
      {thumbSize: '2xl'},
      {only: ['data'], silent: true}
    );

    // Back to the default drops the param rather than naming it.
    thumbSize.value = 'lg';
    expect(merge).toHaveBeenLastCalledWith(
      {thumbSize: null},
      {only: ['data'], silent: true}
    );
  });

  // A table size left in the URL would otherwise render the tiles at it.
  it('gives a view-mode switch the new view’s size', () => {
    const {modeParams} = useElementIndexThumbSize(
      viewStateRef({table: 'md'}),
      visitor,
      () => 'table',
      sourceA
    );

    expect(modeParams('thumbs')).toEqual({thumbSize: null});
    expect(modeParams('structure')).toEqual({thumbSize: 'md'});
  });

  it('restores a persisted size other than the view’s default', () => {
    const {restore} = useElementIndexThumbSize(
      viewStateRef({table: 'lg', thumbs: 'lg'}),
      visitor,
      () => 'table',
      sourceA
    );

    expect(restore()).toEqual({params: {thumbSize: 'lg'}, only: ['data']});
    expect(
      useElementIndexThumbSize(
        viewStateRef({table: 'lg', thumbs: 'lg'}),
        visitor,
        () => 'thumbs',
        sourceA
      ).restore()
    ).toBeNull();
  });

  // The server renders a size the view doesn't offer at its default, so the
  // menu has to agree.
  it('follows the URL’s size, or the default when the view doesn’t offer it', () => {
    const viewState = viewStateRef({table: 'md'});
    const query = {thumbSize: 'lg'};
    const {thumbSize, restore} = useElementIndexThumbSize(
      viewState,
      {...visitor, currentQuery: () => query},
      () => 'table',
      sourceA
    );

    expect(restore()).toBeNull();
    expect(thumbSize.value).toBe('lg');

    query.thumbSize = 'xl';
    expect(restore()).toBeNull();
    expect(thumbSize.value).toBe('sm');
  });

  it('keeps a size per source, and gives a source switch the new source’s size', () => {
    const source = ref('volume:a');
    const {thumbSize, modeParams} = useElementIndexThumbSize(
      viewStateRef(),
      visitor,
      () => 'thumbs',
      () => source.value
    );

    thumbSize.value = 'xl';
    expect(modeParams('thumbs', 'volume:b')).toEqual({thumbSize: null});

    source.value = 'volume:b';
    expect(thumbSize.value).toBe('lg');

    source.value = 'volume:a';
    expect(thumbSize.value).toBe('xl');
  });
});
