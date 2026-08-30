import {
  computed,
  type ComputedRef,
  type Ref,
  ref,
  shallowRef,
  unref,
  watch,
} from 'vue';
import axios, {
  type AxiosError,
  type AxiosInstance,
  type AxiosRequestConfig,
  type AxiosResponse,
  type CancelTokenSource,
} from 'axios';
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
interface UseAxiosOptions<T = FormValue> extends Omit<
  AxiosRequestConfig,
  'url' | 'params'
> {
  immediate?: boolean;
  refetch?: boolean;
  params?: MaybeRef<RequestParams>;
  transform?: (data: T) => T;
  enabled?: MaybeRef<boolean>;
  debounce?: number;
  onSuccess?: (data: T, response: AxiosResponse) => void;
  onError?: (error: AxiosError) => void;
  initialData?: T | null;
  axiosInstance?: AxiosInstance;
}

// Return type interface
interface UseAxiosReturn<T> {
  data: Ref<T | null>;
  error: Ref<unknown>;
  state: Ref<AxiosFetchState>;
  execute: (postData?: RequestData) => Promise<void>;
  isLoading: ComputedRef<boolean>;
  isSuccess: ComputedRef<boolean>;
  isError: ComputedRef<boolean>;
  refetch: () => Promise<void>;
  abort: () => void;
}

export type AxiosFetchState =
  | 'idle'
  | 'loading'
  | 'success'
  | 'error'
  | 'aborted';

export function useFetch<T = FormValue>(
  url: MaybeRef<string>,
  options: UseAxiosOptions<T> = {}
): UseAxiosReturn<T> {
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
    axiosInstance = axios,
    ...axiosOptions
  } = options;

  // Reactive state
  const data = shallowRef<T | null>(initialData);
  const state = ref<AxiosFetchState>('idle');
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

  // Axios cancel token
  let cancelTokenSource: CancelTokenSource | null = null;
  let debounceTimer: ReturnType<typeof setTimeout> | null = null;

  // The actual fetch function
  const execute = async (postData?: RequestData): Promise<void> => {
    if (!computedUrl.value || !computedEnabled.value) return;

    // Cancel previous request
    if (cancelTokenSource) {
      cancelTokenSource.cancel('Request superseded by new request');
    }

    cancelTokenSource = axios.CancelToken.source();
    state.value = 'loading';
    error.value = null;

    try {
      const response = await axiosInstance<T>({
        method: computedMethod.value,
        url: computedUrl.value,
        params: computedParams.value,
        cancelToken: cancelTokenSource.token,
        data: computedMethod.value === 'get' ? undefined : postData,
        ...axiosOptions,
      });

      const transformedData = transform
        ? transform(response.data)
        : response.data;
      state.value = 'success';
      data.value = transformedData;
      onSuccess?.(transformedData, response);
    } catch (err: unknown) {
      if (axios.isCancel(err)) {
        state.value = 'aborted';
      } else if (axios.isAxiosError(err)) {
        console.error('Axios error:', err.response?.data);
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
          if (cancelTokenSource) {
            cancelTokenSource.cancel('Request disabled');
          }
        }
      },
      {immediate, deep: true}
    );
  } else if (immediate && computedEnabled.value) {
    debouncedExecute();
  }

  // Manual refetch function
  const refetch = (): Promise<void> => execute();

  // Cancel function
  const abort = (): void => {
    if (debounceTimer) {
      clearTimeout(debounceTimer);
    }
    if (cancelTokenSource) {
      cancelTokenSource.cancel('Request cancelled by user');
    }
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
  options: UseAxiosOptions<T> = {}
) {
  return useFetch(url, {
    immediate: false,
    ...options,
    method: 'post',
  });
}

export function useActionClient<T = FormValue>(
  url: MaybeRef<string>,
  options: UseAxiosOptions<T> = {}
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
  options: UseAxiosOptions<T> = {}
) {
  const {getApiUrl} = useHelpers();
  const apiUrl = computed(() => getApiUrl(unref(url)));

  return useFetch(apiUrl, {
    ...options,
    axiosInstance: apiClient,
  });
}
