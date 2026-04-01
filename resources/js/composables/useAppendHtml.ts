import {watch} from 'vue';
import {
  appendBodyHtml,
  appendHeadHtml,
} from '../../../packages/craftcms-cp/src';
import {usePage} from '@inertiajs/vue3';

export function useAppendHtml() {
  const page = usePage<{
    headHtml?: string;
    bodyHtml?: string;
  }>();

  watch(
    () => [page.props.headHtml, page.props.bodyHtml],
    async ([headHtml, bodyHtml]) => {
      if (headHtml) {
        await appendHeadHtml(headHtml);
      }

      if (bodyHtml) {
        await appendBodyHtml(bodyHtml);
      }
    }
  );
}
