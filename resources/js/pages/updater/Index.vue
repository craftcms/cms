<script setup lang="ts">
  import {Head} from '@inertiajs/vue3';
  // Watch for finished state to trigger redirect
  import {onBeforeUnmount, onMounted, watch} from 'vue';
  import {
    type UpdaterOption,
    type UpdaterState,
    useUpdater,
  } from '@/modules/updater/composables/useUpdater';
  import {escapeHtml} from '@craftcms/ui/utilities/escapeHtml';
  import {dashboard} from '@/routes/craft/cp';

  defineOptions({layout: []});

  const props = defineProps<{
    title: string;
    initialState: UpdaterState;
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
  } = useUpdater(props.initialState);

  /**
   * Escape status text, then apply markdown-like paragraph, line break, and code formatting
   */
  function parseStatus(status: string): string {
    return (
      '<p>' +
      escapeHtml(status)
        .replace(/\n{2,}/g, '</p><p>')
        .replace(/\n/g, '<br>')
        .replace(/`(.*?)`/g, '<code>$1</code>') +
      '</p>'
    );
  }

  let redirectTimer: ReturnType<typeof setTimeout> | undefined;

  /**
   * Handle successful completion - redirect after a short delay
   */
  function handleFinish(): void {
    redirectTimer = setTimeout(() => {
      window.location.href =
        state.value.returnUrl || props.returnUrl || dashboard.url();
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
    if (props.initialState.nextUrl) {
      executeAction(props.initialState.nextUrl);
    }
  });

  watch(isFinished, (finished) => {
    if (finished) {
      handleFinish();
    }
  });

  onBeforeUnmount(() => clearTimeout(redirectTimer));
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
        <div class="error-message" v-html="parseStatus(state.error)"></div>
        <div
          v-if="state.errorDetails"
          class="error-details"
          tabindex="0"
          v-html="parseStatus(state.errorDetails)"
        ></div>
      </template>
      <div v-else-if="state.status" v-html="parseStatus(state.status)"></div>
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
          :variant="option.submit ? 'accent' : 'neutral'"
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
    background: var(--c-color-neutral-fill-quiet);
  }

  .updater-graphic {
    margin-block-end: var(--c-spacing-lg);

    .spinner {
      --size: 4rem;
    }

    .icon-success {
      color: var(--c-color-success-fill-loud);
      font-size: 4rem;
    }

    .icon-error {
      color: var(--c-color-danger-fill-loud);
      font-size: 4rem;
    }
  }

  .updater-status {
    margin-block-end: var(--c-spacing-lg);
    max-width: 600px;

    :deep(p) {
      margin: 0;
      line-height: 1.5;
    }

    :deep(code) {
      background: var(--c-color-neutral-fill-quiet);
      padding-block: 0.125em;
      padding-inline: 0.375em;
      border-radius: var(--c-radius-sm);
      font-family: var(--c-font-mono);
      font-size: 0.875em;
    }
  }

  .error-message {
    color: var(--c-color-danger-on-quiet);
  }

  .error-details {
    background: var(--c-color-neutral-fill-quiet);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-md);
    padding: var(--c-spacing-md);
    font-family: var(--c-font-mono);
    font-size: 0.8125rem;
    max-width: 600px;
    max-height: 200px;
    overflow: auto;
    text-align: start;
    margin-block-start: var(--c-spacing-md);

    :deep(p) {
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
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--c-spacing-lg);
    border-radius: var(--c-radius-md);
    font-weight: 500;
    text-decoration: none;
    background: var(--c-color-neutral-fill-quiet);
    border: 1px solid var(--c-color-neutral-border-quiet);
    color: var(--c-color-neutral-on-normal);
    cursor: pointer;
    transition:
      background-color 0.15s,
      border-color 0.15s;

    &:hover {
      background: var(--c-color-neutral-fill-normal);
      border-color: var(--c-color-neutral-border-normal);
    }

    &.big {
      padding-block: var(--c-spacing-md);
      padding-inline: var(--c-spacing-xl);
      font-size: 1rem;
    }
  }
</style>
