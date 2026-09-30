import type {ComputedRef, Ref} from 'vue';
import type {Table} from '@tanstack/vue-table';
import type {CheckboxOption} from '@/common/types';
import type {ConditionConfig} from '@/modules/conditions/types';
import type {
  useContentIndexData,
  ElementIndexRow,
} from '../composables/useContentIndexData';
import type {ElementIndexSelection} from '../composables/useElementIndexSelection';
import type {StructureMove} from '../composables/useElementIndexStructure';
import type {ViewMode} from '@/modules/elements/types/view-state';

export interface ElementIndexView {
  elementIndex: Readonly<ReturnType<typeof useContentIndexData>>;
  table: Table<ElementIndexRow>;
  data: ComputedRef<ElementIndexRow[]>;
  selection: ElementIndexSelection;
  search: Ref<string>;
  status: Ref<string>;
  conditions: Ref<ConditionConfig | null>;
  mode: Ref<ViewMode['mode']>;
  sortField: Ref<string>;
  sortDirection: Ref<'asc' | 'desc'>;
  tableColumns: Ref<string[]>;
  columnOptions: ComputedRef<CheckboxOption[]>;
  visibleViewModes: ComputedRef<ViewMode[]>;
  loading: Ref<boolean>;
  submit(this: void): void;
  reorder(this: void, options: CheckboxOption[]): void;
  structureView: {
    isCollapsed(this: void, id: string | number): boolean;
    isPending(this: void, id: string | number): boolean;
  };
  toggleStructure(this: void, id: string | number): void;
  structure?: {
    readonly reorderable: boolean;
    canMoveRow(this: void, id: string | number, move: StructureMove): boolean;
    moveRow(
      this: void,
      id: string | number,
      move: StructureMove
    ): Promise<void>;
  };
}

export interface ElementIndexOperations {
  refresh(this: void, options?: {selectId?: number}): Promise<void>;
  clearSelection(this: void): void;
  findRow(this: void, id: string | number): ElementIndexRow | undefined;
  captureSelection(this: void): () => void;
  view: Pick<ElementIndexView, 'selection'>;
}

export interface ElementIndexModel extends ElementIndexOperations {
  view: ElementIndexView;
  changeSource(this: void, key: string): void;
  changeSite(this: void, handle: string): void;
}
