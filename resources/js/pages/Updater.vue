<script setup lang="ts">
  import {Head} from '@inertiajs/vue3';
  // Watch for finished state to trigger redirect
  import {onMounted, watch} from 'vue';
  import {
    type UpdaterOption,
    type UpdaterState,
    useUpdater,
  } from '@/composables/useUpdater';

  const props = defineProps<{
    title: string;
    initialState: UpdaterState;
    actionPrefix: string;
    returnUrl: string;
  }>();

  const {
    state,
    isLoading,
    hasError,
    isFinished,
    executeAction,
    handleOptionClick,
    getEmailLink,
  } = useUpdater(props.actionPrefix, props.initialState);

  /**
   * Parse status text to support markdown-like formatting
   */
  function parseStatus(status: string): string {
    return status
      .replace(/\n{2,}/g, '</p><p>')
      .replace(/\n/g, '<br>')
      .replace(/`(.*?)`/g, '<code>$1</code>');
  }

  /**
   * Handle successful completion - redirect after a short delay
   */
  function handleFinish(): void {
    setTimeout(() => {
      window.location.href =
        state.value.returnUrl || props.returnUrl || '/admin/dashboard';
    }, 750);
  }

  /**
   * Check if an option is an external link (url or email)
   */
  function isExternalOption(option: UpdaterOption): boolean {
    return !!(option.url || option.email);
  }

  /**
   * Get the href for an external option
   */
  function getOptionHref(option: UpdaterOption): string {
    if (option.url) {
      return option.url;
    }
    if (option.email) {
      return getEmailLink(option);
    }
    return '#';
  }

  // Start the update process when the page loads
  onMounted(() => {
    if (props.initialState.nextAction) {
      executeAction(props.initialState.nextAction);
    }
  });

  watch(isFinished, (finished) => {
    if (finished) {
      handleFinish();
    }
  });
</script>

<template>
  <Head :title="title" />

  <div class="updater">
    <!-- Graphic indicator: spinner, success, or error -->
    <div class="updater-graphic">
      <craft-spinner
        v-if="isLoading && !hasError"
        :visible="true"
        class="spinner"
      ></craft-spinner>
      <craft-icon
        v-else-if="isFinished"
        name="circle-check"
        class="icon-success"
      ></craft-icon>
      <craft-icon
        v-else-if="hasError"
        name="alert-circle"
        class="icon-error"
      ></craft-icon>
    </div>

    <!-- Status message -->
    <div class="updater-status">
      <template v-if="state.error">
        <p class="error-message" v-html="parseStatus(state.error)"></p>
        <div v-if="state.errorDetails" class="error-details" tabindex="0">
          <p v-html="parseStatus(state.errorDetails)"></p>
        </div>
      </template>
      <template v-else-if="state.status">
        <p v-html="parseStatus(state.status)"></p>
      </template>
    </div>

    <!-- Options / action buttons -->
    <div v-if="state.options && !isLoading" class="updater-options">
      <template v-for="option in state.options" :key="option.label">
        <!-- External link (url or email) -->
        <a
          v-if="isExternalOption(option)"
          :href="getOptionHref(option)"
          :target="option.url ? '_blank' : undefined"
          class="btn big"
        >
          {{ option.label }}
        </a>
        <!-- Action button -->
        <craft-button
          v-else
          type="button"
          @click="handleOptionClick(option)"
          :variant="option.submit ? 'primary' : 'default'"
          size="lg"
        >
          {{ option.label }}
        </craft-button>
      </template>
    </div>
  </div>
</template>

<style scoped lang="scss">
  .updater {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: var(--c-spacing-xl);
    text-align: center;
    background: var(--c-color-neutral-bg);
  }

  .updater-graphic {
    margin-bottom: var(--c-spacing-lg);

    .spinner {
      --size: 4rem;
    }

    .icon-success {
      color: var(--c-color-success-bg-emphasis);
      font-size: 4rem;
    }

    .icon-error {
      color: var(--c-color-danger-bg-emphasis);
      font-size: 4rem;
    }
  }

  .updater-status {
    margin-bottom: var(--c-spacing-lg);
    max-width: 600px;

    p {
      margin: 0;
      line-height: 1.5;
    }

    :deep(code) {
      background: var(--c-color-neutral-bg-quiet);
      padding: 0.125em 0.375em;
      border-radius: var(--c-radius-sm);
      font-family: var(--c-font-mono);
      font-size: 0.875em;
    }
  }

  .error-message {
    color: var(--c-color-danger-on-quiet);
  }

  .error-details {
    background: var(--c-color-neutral-bg-quiet);
    border: 1px solid var(--c-color-neutral-border);
    border-radius: var(--c-radius-md);
    padding: var(--c-spacing-md);
    font-family: var(--c-font-mono);
    font-size: 0.8125rem;
    max-width: 600px;
    max-height: 200px;
    overflow: auto;
    text-align: left;
    margin-top: var(--c-spacing-md);

    p {
      white-space: pre-wrap;
      word-break: break-word;
    }
  }

  .updater-options {
    display: flex;
    gap: var(--c-spacing-sm);
    flex-wrap: wrap;
    justify-content: center;
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: var(--c-spacing-sm) var(--c-spacing-lg);
    border-radius: var(--c-radius-md);
    font-weight: 500;
    text-decoration: none;
    background: var(--c-color-neutral-bg);
    border: 1px solid var(--c-color-neutral-border);
    color: var(--c-color-neutral-on);
    cursor: pointer;
    transition:
      background-color 0.15s,
      border-color 0.15s;

    &:hover {
      background: var(--c-color-neutral-bg-hover);
      border-color: var(--c-color-neutral-border-hover);
    }

    &.big {
      padding: var(--c-spacing-md) var(--c-spacing-xl);
      font-size: 1rem;
    }
  }
</style>
