import type {Ref} from 'vue';
import {useForm} from '@inertiajs/vue3';
import {index} from '@/routes/craft/cp/content/index.js';
import type {ViewState} from '@/modules/elements/types/view-state';
import type {SourceItem} from '@/modules/elements/types/sources';
import type {ConditionConfig} from '@/modules/elements/composables/useConditionBuilder';

interface ElementIndexFiltersContext {
  search?: string | null;
  status: string | null;
  page: string;
  sectionHandle?: string | number;
  source?: SourceItem | null;
}

/**
 * The element index's filter form. It owns only the user-entered filters
 * (search + status); sort, view mode, and the filter HUD's condition are
 * owned elsewhere and sent alongside the filters at submit time.
 */
export function useElementIndexFilters(
  props: ElementIndexFiltersContext,
  viewState: Ref<ViewState>,
  conditions?: Ref<ConditionConfig | null>
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
        condition: conditions?.value ?? undefined,
      }))
      .submit(
        index({
          page: props.page ?? '',
          sectionHandle: props.sectionHandle ?? undefined,
        }),
        {
          // `condition.conditionRules` and `sort` are arrays of objects.
          // Inertia's default 'brackets' format serializes those as repeated
          // `key[][field]=...` params, and PHP's parse_str allocates a new
          // array element per `[]` — splitting each object into per-field
          // fragments. Index-style keys keep them intact.
          queryStringArrayFormat: 'indices',
        }
      );
  }

  return {form, submit};
}
