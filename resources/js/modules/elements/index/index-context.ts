import type {ComputedRef, InjectionKey} from 'vue';
import type {SourceItem} from '@/modules/elements/types/sources';
import type {QueryParams} from '@/common/types/query';

export interface ElementIndexContext {
  elementType: string;
  context: string;
  source: SourceItem;
  fieldLayouts?: QueryParams[];
  extraParams?: Record<string, unknown>;
}

export const elementIndexContextKey: InjectionKey<
  ComputedRef<ElementIndexContext | null>
> = Symbol('element-index-context');
