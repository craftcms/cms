import {usePage} from '@inertiajs/vue3';
import {watch} from 'vue';
import {appendBodyHtml, appendHeadHtml} from '@craftcms/cp';

export function useAssetBridge() {
  const page = usePage<{
    bodyHtml?: string;
    headHtml?: string;
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
    },
    {immediate: true}
  );
}
