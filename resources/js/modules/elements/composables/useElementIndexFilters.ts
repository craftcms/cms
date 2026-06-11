import type {Ref} from 'vue';
import {useForm} from '@inertiajs/vue3';
import {index} from '@/routes/craft/cp/content/index.js';
import type {ViewState} from '@/modules/elements/types/view-state';

interface ElementIndexFiltersContext {
  search?: string | null;
  status: string | null;
  page: string;
  sectionHandle?: string | number;
}

/**
 * The element index's filter form. It owns only the user-entered filters
 * (search + status); sort and view mode are owned by the view state and sent
 * alongside the filters at submit time.
 */
export function useElementIndexFilters(
  props: ElementIndexFiltersContext,
  viewState: Ref<ViewState>
) {
  const form = useForm({
    search: props.search ?? '',
    status: props.status ?? '',
  });

  function submit() {
    form
      .transform((data) => ({
        ...data,
        sort: viewState.value.sort,
        viewMode: viewState.value.mode,
      }))
      .submit(
        index({
          page: props.page ?? '',
          sectionHandle: props.sectionHandle ?? undefined,
        })
      );
  }

  return {form, submit};
}
