import type {InjectionKey, Ref} from 'vue';
import type {SlideoutSaveResult} from '@/common/slideouts';

export interface CreateSlideout {
  width?: string;
  onSaved: (result: SlideoutSaveResult) => void;
}

/**
 * Set by a table whose create action opens in a slideout rather than
 * navigating, wherever its create button ends up rendering.
 */
export const AdminTableCreateSlideout: InjectionKey<
  Readonly<Ref<CreateSlideout | null>>
> = Symbol('AdminTableCreateSlideout');
