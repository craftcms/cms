import {
  computed,
  type ComputedRef,
  type Ref,
  ref,
  shallowRef,
  unref,
  watch,
} from 'vue';
import {
  http,
  isCancel,
  isHttpError,
  type HttpClient,
  type HttpError,
  type HttpRequestConfig,
  type HttpResponse,
} from '@craftcms/ui/utilities/api/http';
import {useHelpers} from '@/common/composables/useCraftData';
import {apiClient} from '@craftcms/ui/utilities/api/apiClient';
import type {FormValue, FormValues} from '@/modules/forms/types';

// Type for URL parameter - can be string, ref, or computed
type MaybeRef<T> = T | Ref<T> | ComputedRef<T>;

type RequestParams = FormValues | URLSearchParams;
type RequestData =
  | FormValues
  | FormData
  | URLSearchParams
  | string
  | Blob
  | null;

// Options interface
interface UseFetchOptions<T = FormValue> extends Omit<
  HttpRequestConfig,
  'url' | 'params'
> {
  immediate?: boolean;
  refetch?: boolean;
  params?: MaybeRef<RequestParams>;
  transform?: (data: T) => T | Promise<T>;
  enabled?: MaybeRef<boolean>;
  debounce?: number;
  onSuccess?: (data: T, response: HttpResponse) => void;
  onError?: (error: HttpError) => void;
  initialData?: T | null;
  client?: HttpClient;
}

// Return type interface
interface UseFetchReturn<T> {
  data: Ref<T | null>;
  error: Ref<unknown>;
  state: Ref<FetchState>;
  execute: (postData?: RequestData) => Promise<T | undefined>;
  isLoading: ComputedRef<boolean>;
  isSuccess: ComputedRef<boolean>;
  isError: ComputedRef<boolean>;
  refetch: () => Promise<T | undefined>;
  abort: () => void;
}

export type FetchState = 'idle' | 'loading' | 'success' | 'error' | 'aborted';

export function useFetch<T = FormValue>(
  url: MaybeRef<string>,
  options: UseFetchOptions<T> = {}
): UseFetchReturn<T> {
  // Options with defaults
  const {
    immediate = true,
    refetch: refetchOption = true,
    params,
    enabled = true,
    debounce = 0,
    transform,
    onSuccess,
    onError,
    initialData = null,
    method = 'get',
    client = http,
    signal,
    ...requestOptions
  } = options;

  // Reactive state
  const data = shallowRef<T | null>(initialData);
  const state = ref<FetchState>('idle');
  const error = ref<unknown>(null);

  const isLoading = computed(() => state.value === 'loading');
  const isSuccess = computed(() => state.value === 'success');
  const isError = computed(() => state.value === 'error');

  const computedUrl = computed<string>(() => unref(url));
  const computedEnabled = computed<boolean>(() => unref(enabled));
  const computedParams = computed<RequestParams | undefined>(() =>
    unref(params)
  );

  const computedMethod = computed<string>(() => unref(method.toLowerCase()));

  // Aborts the in-flight request when it's superseded, disabled, or aborted
  let controller: AbortController | null = null;
  let debounceTimer: ReturnType<typeof setTimeout> | null = null;

  // The actual fetch function
  const execute = async (postData?: RequestData): Promise<T | undefined> => {
    if (!computedUrl.value || !computedEnabled.value) return;

    // Cancel previous request
    controller?.abort();

    const request = new AbortController();
    controller = request;
    // A caller-provided signal can also abort the request.
    if (signal?.aborted) {
      request.abort();
    } else {
      signal?.addEventListener('abort', () => request.abort(), {once: true});
    }
    state.value = 'loading';
    error.value = null;

    try {
      const response = await client.request<T>({
        method: computedMethod.value,
        url: computedUrl.value,
        params: computedParams.value,
        signal: request.signal,
        data: computedMethod.value === 'get' ? undefined : postData,
        ...requestOptions,
      });

      request.signal.throwIfAborted();
      const transformedData = transform
        ? await transform(response.data)
        : response.data;
      request.signal.throwIfAborted();

      state.value = 'success';
      data.value = transformedData;
      onSuccess?.(transformedData, response);

      return transformedData;
    } catch (err: unknown) {
      if (request !== controller) return;

      if (isCancel(err) || request.signal.aborted) {
        state.value = 'aborted';
      } else if (isHttpError(err)) {
        console.error('HTTP error:', err.response?.data);
        state.value = 'error';
        error.value = err.response?.data || err.message || 'Unknown error';
        onError?.(err);
      } else if (err instanceof Error) {
        console.error('Unknown error:', err.message);
        state.value = 'error';
        error.value = err.message || 'Unknown error';
      } else {
        console.error('Unknown error:', err);
        state.value = 'error';
        error.value = 'Unknown error';
      }
    }
  };

  const debouncedExecute = (): void => {
    // Clear existing timer
    if (debounceTimer) {
      clearTimeout(debounceTimer);
    }

    if (debounce > 0) {
      debounceTimer = setTimeout(() => {
        execute();
      }, debounce);
    } else {
      execute();
    }
  };

  // Watch for changes in URL, params, and enabled state
  if (refetchOption) {
    watch(
      [computedUrl, computedParams, computedEnabled],
      () => {
        if (computedEnabled.value) {
          debouncedExecute();
        } else {
          // Clear debounce timer and cancel request when disabled
          if (debounceTimer) {
            clearTimeout(debounceTimer);
          }
          controller?.abort();
        }
      },
      {immediate, deep: true}
    );
  } else if (immediate && computedEnabled.value) {
    debouncedExecute();
  }

  // Manual refetch function
  const refetch = (): Promise<T | undefined> => execute();

  // Cancel function
  const abort = (): void => {
    if (debounceTimer) {
      clearTimeout(debounceTimer);
    }
    controller?.abort();
  };

  return {
    data,
    error,
    state,
    isLoading,
    isSuccess,
    isError,
    execute,
    refetch,
    abort,
  };
}

export function usePost<T = FormValue>(
  url: MaybeRef<string>,
  options: UseFetchOptions<T> = {}
) {
  return useFetch(url, {
    immediate: false,
    ...options,
    method: 'post',
  });
}

export function useActionClient<T = FormValue>(
  url: MaybeRef<string>,
  options: UseFetchOptions<T> = {}
) {
  const method = options.method ?? 'POST';

  const {getActionUrl} = useHelpers();
  const actionUrl = computed(() => getActionUrl(unref(url)));

  return useFetch(actionUrl, {
    immediate: false,
    ...options,
    method,
  });
}

export function useApiClient<T = FormValue>(
  url: MaybeRef<string>,
  options: UseFetchOptions<T> = {}
) {
  const {getApiUrl} = useHelpers();
  const apiUrl = computed(() => getApiUrl(unref(url)));

  return useFetch(apiUrl, {
    ...options,
    client: apiClient,
  });
}
