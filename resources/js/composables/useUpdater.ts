import {computed, type ComputedRef, type Ref, ref} from 'vue';
import {t} from '@craftcms/cp';
import axios from 'axios';

/**
 * State returned from updater API endpoints
 */
export interface UpdaterState {
  status?: string;
  error?: string;
  errorDetails?: string;
  nextAction?: string;
  options?: UpdaterOption[];
  finished?: boolean;
  returnUrl?: string;
  data: string;
}

/**
 * An option/button the user can click when faced with an error or decision
 */
export interface UpdaterOption {
  label: string;
  url?: string;
  email?: string;
  subject?: string;
  submit?: boolean;
  nextAction?: string;
  status?: string;
  data?: string;
}

interface UseUpdaterReturn {
  state: Ref<UpdaterState>;
  isLoading: Ref<boolean>;
  hasError: ComputedRef<boolean>;
  isFinished: ComputedRef<boolean>;
  executeAction: (action: string) => Promise<void>;
  handleOptionClick: (option: UpdaterOption) => void;
  getEmailLink: (option: UpdaterOption) => string;
}

/**
 * Composable for managing the updater workflow state machine
 */
export function useUpdater(
  actionPrefix: string,
  initialState: UpdaterState
): UseUpdaterReturn {
  const state = ref<UpdaterState>({...initialState});
  const isLoading = ref(false);

  const hasError = computed(() => !!state.value.error);
  const isFinished = computed(() => !!state.value.finished);

  /**
   * Execute an updater action via POST request
   */
  async function executeAction(action: string): Promise<void> {
    isLoading.value = true;

    try {
      const response = await axios.post(
        `/admin/actions/${actionPrefix}/${action}`,
        {data: state.value.data},
        {
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
          },
        }
      );

      handleStateUpdate(response.data);
    } catch (error: any) {
      handleFatalError(error);
    }

    isLoading.value = false;
  }

  /**
   * Process state update from server response
   */
  function handleStateUpdate(newState: UpdaterState): void {
    // Preserve the encrypted data token
    if (newState.data) {
      state.value.data = newState.data;
    }

    // Update state with new values
    state.value = {
      ...state.value,
      status: newState.status,
      error: newState.error,
      errorDetails: newState.errorDetails,
      options: newState.options,
      finished: newState.finished,
      returnUrl: newState.returnUrl ?? state.value.returnUrl,
      nextAction: newState.nextAction,
    };

    // Auto-execute next action if specified
    if (newState.nextAction) {
      executeAction(newState.nextAction);
    }
  }

  /**
   * Handle user clicking an option button
   */
  function handleOptionClick(option: UpdaterOption): void {
    // If option has a nextAction, execute it
    if (option.nextAction) {
      // Clear error state and show new status
      state.value.error = undefined;
      state.value.errorDetails = undefined;
      state.value.options = undefined;

      if (option.status) {
        state.value.status = option.status;
      }

      // If the option includes data, use it
      if (option.data) {
        state.value.data = option.data;
      }

      executeAction(option.nextAction);
    }
  }

  /**
   * Handle fatal/unexpected errors
   */
  function handleFatalError(error: any): void {
    const errorMessage =
      error.response?.data?.message || error.message || 'Unknown error';
    const statusText = error.response?.statusText || 'Error';

    state.value.error = t('A fatal error has occurred:');
    state.value.errorDetails = `${t('Status:')} ${statusText}\n\n${t('Response:')} ${errorMessage}`;
    state.value.options = [
      {
        label: t('Troubleshoot'),
        url: 'https://craftcms.com/knowledge-base/failed-updates',
      },
      {
        label: t('Send for help'),
        email: 'support@craftcms.com',
      },
    ];

    // Try to disable maintenance mode
    axios
      .post(
        `/admin/actions/${actionPrefix}/finish`,
        {data: state.value.data},
        {
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
          },
        }
      )
      .catch(() => {
        // Ignore errors when trying to finish
      });
  }

  /**
   * Generate mailto link for support email options
   */
  function getEmailLink(option: UpdaterOption): string {
    const subject = encodeURIComponent(
      option.subject || 'Craft update failure'
    );
    let body = 'Describe what happened here.';

    if (state.value.errorDetails) {
      body +=
        '\n\n-----------------------------------------------------------\n\n' +
        state.value.errorDetails;
    }

    return `mailto:${option.email}?subject=${subject}&body=${encodeURIComponent(body)}`;
  }

  return {
    state,
    isLoading,
    hasError,
    isFinished,
    executeAction,
    handleOptionClick,
    getEmailLink,
  };
}
