import {
  appendHeadHtml,
  appendBodyHtml,
  type AppendHtmlDisposer,
} from '@craftcms/ui';
import {computed, watch, onBeforeUnmount} from 'vue';
import {usePage} from '@inertiajs/vue3';

interface HtmlContent {
  headHtml?: string;
  bodyHtml?: string;
}

/**
 * Composable for appending HTML to the document head or body.
 *
 * Handles deduplication of CSS and JS resources automatically,
 * ensuring that stylesheets and scripts are only loaded once.
 * headHtml is always loaded and executed before bodyHtml.
 *
 * Appended nodes are tracked per composable invocation and are removed
 * when the source HTML changes or when the host component unmounts.
 * This keeps page-specific markup from leaking across Inertia navigations.
 *
 * @example
 * // With props (re-runs on Inertia navigation when props change)
 * const props = defineProps<{ headHtml?: string; bodyHtml?: string }>();
 * useAppendHtml(props);
 *
 * @example
 * // Manual usage (returned disposer is up to the caller to invoke)
 * const {appendHead, appendBody} = useAppendHtml();
 * const dispose = await appendBody('<script src="/script.js"></script>');
 * // later: dispose();
 */
export function useAppendHtml(source?: HtmlContent) {
  const page = usePage<{
    headHtml?: string;
    bodyHtml?: string;
  }>();
  const computedSource = computed(() => source || page.props);

  const disposers: AppendHtmlDisposer[] = [];

  const disposeAll = () => {
    while (disposers.length) {
      disposers.pop()?.();
    }
  };

  if (computedSource.value) {
    watch(
      () => ({
        headHtml: computedSource.value.headHtml,
        bodyHtml: computedSource.value.bodyHtml,
      }),
      async (value) => {
        disposeAll();

        if (value.headHtml) {
          disposers.push(await appendHeadHtml(value.headHtml));
        }

        if (value.bodyHtml) {
          disposers.push(await appendBodyHtml(value.bodyHtml));
        }
      },
      {immediate: true}
    );
  }

  onBeforeUnmount(disposeAll);

  return {
    appendHead: appendHeadHtml,
    appendBody: appendBodyHtml,
  };
}
