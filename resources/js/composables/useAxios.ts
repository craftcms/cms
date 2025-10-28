import {
  ref,
  computed,
  watch,
  unref,
  type Ref,
  type ComputedRef,
  type UnwrapRef,
} from 'vue';
import axios, {
  type AxiosError,
  type AxiosRequestConfig,
  type AxiosResponse,
  type CancelTokenSource,
} from 'axios';

// Type for URL parameter - can be string, ref, or computed
type MaybeRef<T> = T | Ref<T> | ComputedRef<T>;

// Options interface
interface UseAxiosOptions<T = any>
  extends Omit<AxiosRequestConfig, 'url' | 'params'> {
  immediate?: boolean;
  refetch?: boolean;
  params?: MaybeRef<Record<string, any>>;
  transform?: (data: T) => T;
  enabled?: MaybeRef<boolean>;
  debounce?: number;
  onSuccess?: (data: T, response: AxiosResponse) => void;
  onError?: (error: any) => void;
  initialData?: T | null;
}

// Return type interface
interface UseAxiosReturn<T> {
  data: Ref<UnwrapRef<T> | null>;
  error: any;
  state: Ref<AxiosFetchState>;
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

export function useAxios<T = any>(
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
    transform = (data: any) => data as T,
    onSuccess,
    onError,
    initialData = null,
    ...axiosOptions
  } = options;

  // Reactive state
  const data = ref(initialData) as Ref<UnwrapRef<T> | null>;
  const state = ref<AxiosFetchState>('idle');
  const error = ref(null);

  const isLoading = computed(() => state.value === 'loading');
  const isSuccess = computed(() => state.value === 'success');
  const isError = computed(() => state.value === 'error');

  const computedUrl = computed<string>(() => unref(url));
  const computedEnabled = computed<boolean>(() => unref(enabled));
  const computedParams = computed<Record<string, any> | undefined>(() =>
    unref(params)
  );

  // Axios cancel token
  let cancelTokenSource: CancelTokenSource | null = null;
  let debounceTimer: number | null = null;

  // The actual fetch function
  const execute = async (): Promise<void> => {
    if (!computedUrl.value || !computedEnabled.value) return;

    // Cancel previous request
    if (cancelTokenSource) {
      cancelTokenSource.cancel('Request superseded by new request');
    }

    cancelTokenSource = axios.CancelToken.source();
    state.value = 'loading';
    error.value = null;

    try {
      const response = await axios<T>({
        url: computedUrl.value,
        params: computedParams.value,
        cancelToken: cancelTokenSource.token,
        ...axiosOptions,
      });

      const transformedData = transform(response.data);
      state.value = 'success';
      data.value = transformedData as UnwrapRef<T>;
      onSuccess?.(transformedData, response);
    } catch (err: AxiosError | any) {
      if (axios.isCancel(err)) {
        state.value = 'aborted';
      } else if (axios.isAxiosError(err)) {
        console.log('Axios error:', err.response?.data);
        state.value = 'error';
        error.value = err.response?.data || err.message || 'Unknown error';
        onError?.(err);
      } else {
        console.log('Unkown error:', err.message);
        state.value = 'error';
        error.value = err.message || 'Unknown error';
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
    refetch,
    abort,
  };
}
