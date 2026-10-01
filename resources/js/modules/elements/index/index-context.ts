import type {ComputedRef, InjectionKey} from 'vue';
import type {SourceItem} from '@/modules/elements/types/sources';

export interface ElementIndexContext {
  elementType: string;
  context: string;
  source: SourceItem;
  fieldLayouts?: Array<Record<string, string | number | boolean | null>>;
  extraParams?: Record<string, unknown>;
}

export const elementIndexContextKey: InjectionKey<
  ComputedRef<ElementIndexContext | null>
> = Symbol('element-index-context');
