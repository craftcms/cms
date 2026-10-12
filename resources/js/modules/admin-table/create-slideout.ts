import type {InjectionKey, Ref} from 'vue';

export interface CreateSlideout {
  width?: string;
  onSaved: () => void;
}

/**
 * Set by a table whose create action opens in a slideout rather than
 * navigating, wherever its create button ends up rendering.
 */
export const AdminTableCreateSlideout: InjectionKey<
  Readonly<Ref<CreateSlideout | null>>
> = Symbol('AdminTableCreateSlideout');
