import type {ComputedRef, Ref} from 'vue';
import type {Table} from '@tanstack/vue-table';
import type {CraftTableFeatures} from '@/common/table/craftTable';
import type {CheckboxOption} from '@/common/types';
import type {ConditionConfig} from '@/modules/conditions/types';
import type {
  useContentIndexData,
  ElementIndexRow,
} from '../composables/useContentIndexData';
import type {TableRowSelection} from '@/common/composables/useTableRowSelection';
import type {StructureMove} from '../composables/useElementIndexStructure';
import type {ViewMode} from '@/modules/elements/types/view-state';
import type {InlineEditingSaveResult} from '../composables/useInlineEditing';
import type {ElementIndexExportFormat} from './exporters';
import type {ElementIndexContext} from '../index-context';

export interface ElementIndexInlineEditing {
  active: Ref<boolean>;
  load(this: void): Promise<void>;
  save(
    this: void,
    body: URLSearchParams
  ): Promise<InlineEditingSaveResult | false>;
}

export type ExportElementIndex = (
  format: ElementIndexExportFormat,
  type: string,
  selectedIds: ReadonlyArray<string | number>,
  limit?: number
) => void | Promise<void>;

export interface ElementIndexView {
  elementIndex: Readonly<ReturnType<typeof useContentIndexData>>;
  table: Table<CraftTableFeatures, ElementIndexRow>;
  data: ComputedRef<ElementIndexRow[]>;
  selection: TableRowSelection<ElementIndexRow>;
  search: Ref<string>;
  status: Ref<string>;
  conditions: Ref<ConditionConfig | null>;
  mode: Ref<ViewMode['mode']>;
  inlineEditing?: ElementIndexInlineEditing;
  sortField: Ref<string>;
  sortDirection: Ref<'asc' | 'desc'>;
  tableColumns: Ref<string[]>;
  columnOptions: ComputedRef<CheckboxOption[]>;
  visibleViewModes: ComputedRef<ViewMode[]>;
  loading: Ref<boolean>;
  processing: ComputedRef<boolean>;
  filterContext: ComputedRef<ElementIndexContext | null>;
  exportElements?: ExportElementIndex;
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
  rowReorder?: {
    enabled: ComputedRef<boolean>;
    move(this: void, from: number, to: number): void | Promise<void>;
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
