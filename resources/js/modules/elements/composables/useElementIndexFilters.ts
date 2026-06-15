import type {Ref} from 'vue';
import {useForm} from '@inertiajs/vue3';
import {index} from '@/routes/craft/cp/content/index.js';
import type {ViewState} from '@/modules/elements/types/view-state';
import type {SourceItem} from '@/modules/elements/types/sources';

interface ElementIndexFiltersContext {
  search?: string | null;
  status: string | null;
  page: string;
  sectionHandle?: string | number;
  source?: SourceItem | null;
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
    // Preserve the active per-source sort across the filter submit; an absent
    // entry lets the server fall back to the source's `defaultSort`.
    const sourceKey = props.source?.key ?? '*';

    form
      .transform((data) => ({
        ...data,
        sort: viewState.value.sources?.[sourceKey]?.sort,
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
