import {watchEffect} from 'vue';
import {setLayoutProps} from '@inertiajs/vue3';
import type {InertiaForm} from '@inertiajs/vue3';
import {useScreenPropsStore} from '@/common/composables/screen';
import type {
  ActionItem,
  ActionItemButton,
  FormSaveOptions,
} from '@/common/types';

export interface UseAppLayoutOptions {
  title?: string;
  fullWidth?: boolean;
  form?: InertiaForm<any> | null;
  defaultFormActions?: Array<'saveAndContinueEditing'>;
  formActions?: Array<ActionItem>;
  formAdditionalActions?: Array<ActionItem>;
  formAdditionalButtons?: Array<ActionItemButton>;
  onSave?: (options?: FormSaveOptions) => void;
}

/**
 * Configures the ambient `AppLayout` for pages that do NOT render
 * `<AppLayout>` inline (i.e. pages that keep the default Inertia layout
 * instead of opting out with `defineOptions({layout: []})`).
 *
 * Props are pushed into the layout via Inertia's layout-props store, which
 * Inertia resets automatically on navigation, so pages don't need to clean
 * up after themselves.
 *
 * Inside a slideout they go to the panel's own store instead: Inertia's
 * layout props address the single layout Inertia rendered, so a slideout
 * writing to them would reconfigure the base page underneath it.
 *
 * Pass a getter when any option derives from reactive state (a computed,
 * props that can change after mount, …); it re-applies whenever its
 * reactive dependencies change. A plain object is applied as a one-time
 * snapshot.
 */
export function useAppLayout(
  options: UseAppLayoutOptions | (() => UseAppLayoutOptions)
): void {
  const resolve = options instanceof Function ? options : () => options;
  const screenProps = useScreenPropsStore();

  // `setLayoutProps` types its `props` argument as `Partial<LayoutProps>`,
  // which defaults to `Record<string, unknown>` (an index signature) unless
  // the app augments Inertia's `LayoutProps` config type. Cast through
  // `Record<string, unknown>` since `UseAppLayoutOptions` is structurally
  // compatible but doesn't declare an index signature.
  const apply = screenProps
    ? (props: UseAppLayoutOptions) => screenProps.set(props)
    : (props: UseAppLayoutOptions) =>
        setLayoutProps<UseAppLayoutOptions>(props);

  watchEffect(() => apply(resolve()));
}
