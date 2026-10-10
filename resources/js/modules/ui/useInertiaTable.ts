import {router, usePage} from '@inertiajs/vue3';
import {computed, nextTick} from 'vue';
import {t} from '@craftcms/ui';
import type {
  AdminTablePage,
  AdminTableRequest,
} from '@/modules/admin-table/types';
import type {TableNodePayload, TableRow} from './table-types';

export function useInertiaTable(getNode: () => TableNodePayload) {
  const page = usePage();
  const requestState = computed<AdminTableRequest | undefined>(() => {
    const pagination = getNode().props.pagination;
    if (!pagination) return;

    const params = new URL(page.url, location.origin).searchParams;
    const field = params.get('sort[0][field]');
    return {
      page: pagination.current_page,
      perPage: pagination.per_page,
      search: params.get('search') ?? undefined,
      status: params.get('status') ?? undefined,
      sort: field
        ? [
            {
              field,
              direction:
                params.get('sort[0][direction]') === 'desc' ? 'desc' : 'asc',
            },
          ]
        : undefined,
    };
  });

  function loadRows(
    request: AdminTableRequest,
    signal: AbortSignal
  ): Promise<AdminTablePage<TableRow>> {
    signal.throwIfAborted();
    const pageParam = Craft.pageTrigger ?? 'page';
    const url = new URL(page.url, location.origin);
    const query = Object.fromEntries(url.searchParams);
    for (const key of Object.keys(query)) {
      if (
        key === pageParam ||
        key === 'per_page' ||
        key === 'search' ||
        key === 'status' ||
        /^sort(?:\[|$)/.test(key)
      )
        delete query[key];
    }

    return new Promise((resolve, reject) => {
      let cancel: (() => void) | undefined;
      let successful = false;
      const abort = () => {
        cancel?.();
        reject(new DOMException('Table request canceled.', 'AbortError'));
      };
      signal.addEventListener('abort', abort, {once: true});

      router.get(
        url.pathname,
        {
          ...query,
          [pageParam]: request.page,
          per_page: request.perPage,
          ...(request.search ? {search: request.search} : {}),
          ...(request.status ? {status: request.status} : {}),
          ...(request.sort
            ? {
                sort: Object.fromEntries(
                  request.sort.map((sort, index) => [index, sort])
                ),
              }
            : {}),
        },
        {
          only: ['ui'],
          preserveState: true,
          preserveScroll: true,
          onCancelToken: (token) => {
            cancel = () => token.cancel();
            if (signal.aborted) abort();
          },
          onCancel: () =>
            reject(new DOMException('Table request canceled.', 'AbortError')),
          onError: reject,
          onSuccess: async () => {
            successful = true;
            await nextTick();
            const {rows, pagination} = getNode().props;
            if (!pagination) {
              reject(new Error(t('Couldn’t load table data.')));
              return;
            }
            resolve({rows, pagination});
          },
          onFinish: () => {
            signal.removeEventListener('abort', abort);
            if (!successful) reject(new Error(t('Couldn’t load table data.')));
          },
        }
      );
    });
  }

  return {loadRows, requestState};
}
