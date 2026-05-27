import {appendHeadHtml, appendBodyHtml} from '@craftcms/cp';
import {watch} from 'vue';

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
 * @example
 * // With props
 * const props = defineProps<{ headHtml?: string; bodyHtml?: string }>();
 * useAppendHtml(props);
 *
 * @example
 * // Manual usage
 * const {appendHead, appendBody} = useAppendHtml();
 * await appendHead('<link rel="stylesheet" href="/styles.css">');
 * await appendBody('<script src="/script.js"></script>');
 */
export function useAppendHtml(source?: HtmlContent) {
  if (source) {
    watch(
      () => ({headHtml: source.headHtml, bodyHtml: source.bodyHtml}),
      async (value) => {
        if (value.headHtml) {
          await appendHeadHtml(value.headHtml);
        }

        if (value.bodyHtml) {
          await appendBodyHtml(value.bodyHtml);
        }
      },
      {immediate: true}
    );
  }

  return {
    appendHead: appendHeadHtml,
    appendBody: appendBodyHtml,
  };
}
