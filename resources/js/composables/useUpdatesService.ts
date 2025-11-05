import {actionClient, apiClient} from '@craftcms/cp';
import {ref} from 'vue';
import axios, {AxiosError, type AxiosResponse} from 'axios';

interface UpdatesResponseData {
  cms: {
    status: 'breakpoint' | 'eligible' | 'expired';
    releases: Array<any>;
    renewalUrl?: string;
    renewalPrice?: string;
    renewalCurrency?: string;
    phpConstraint?: string;
    packageName?: string;
  };
  plugins: Record<string, any>;
}

async function cacheUpdates(
  updates: UpdatesResponseData,
  includeDetails = false
) {
  const data = {
    updates,
    includeDetails,
  };

  return actionClient.post('app/cache-updates', data);
}

async function getUpdates(includeDetails = false) {
  const {data} = await apiClient.get<UpdatesResponseData>('updates', {
    data: {
      onlyIfCached: false,
      includeDetails,
    },
  });

  return cacheUpdates(data, includeDetails);
}

async function _checkForUpdates(forceRefresh = false, includeDetails = false) {
  if (!forceRefresh) {
    // Check if we have cached info first
    const {data: info} = await actionClient.post('app/check-for-updates', {
      onlyIfCached: true,
      includeDetails,
    });


    if (info.cached) {
      return info;
    }

    return getUpdates(includeDetails);
  } else {
    return getUpdates(includeDetails);
  }
}

export function useUpdatesCheck(
  options: {
    enabled?: boolean;
    initialData?: Record<any, any>;
  } = {}
) {
  const {initialData = {}, enabled = true} = options;

  const state = ref<null | 'pending' | 'error' | 'success'>(
    enabled ? null : 'success'
  );
  const data = ref<Record<any, any> | null>(initialData);
  const response = ref<AxiosResponse<any> | null>(null);
  const error = ref<unknown | null>(null);

  async function checkForUpdates(forceRefresh = false) {
    if (state.value === 'pending') {
      return;
    }

    state.value = 'pending';
    try {
      const response = await _checkForUpdates(forceRefresh);
      state.value = 'success';
      data.value = response.data;
      error.value = null;
    } catch (e: AxiosError | unknown) {
      state.value = 'error';
      if (axios.isAxiosError(e)) {
        if (e.response) {
          error.value =
            e.response.data?.message ||
            `Request failed with status ${e.response.status}`;
        } else if (e.request) {
          error.value = e.request;
        } else {
          error.value = e.message;
        }
      } else {
        error.value = e;
      }
    }
  }

  const refetch = checkForUpdates;

  if (enabled) {
    checkForUpdates();
  }

  return {
    state,
    data,
    error,
    refetch,
    response,
  };
}
