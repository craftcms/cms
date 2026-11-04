<script setup lang="ts">
  import {computed, nextTick, ref, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import {firstFocusableWithin} from '@/common/utils/dom';
  import type {ServerRenderedControlStatus} from './useServerRenderedControl';

  /**
   * Wraps a server-rendered control, holding its space with a placeholder
   * until its HTML first loads, and offering a retry when a render fails.
   */
  const props = withDefaults(
    defineProps<{
      status: ServerRenderedControlStatus;
      /** Whether the control's HTML has loaded at least once. */
      hasHtml: boolean;
      /** Placeholder chip rows, one per selected item. */
      chips?: number;
      /** Placeholder buttons below the chips, or below each group's chips. */
      buttons?: number;
      /** Placeholder group boxes, given as each group's chip rows. Replaces `chips`. */
      groups?: number[];
      /** Whether an “Add Group” placeholder button follows the groups. */
      addGroup?: boolean;
      /** The block size of a single placeholder block, for controls with no chips. */
      blockSize?: string;
    }>(),
    {
      chips: 0,
      buttons: 0,
      groups: undefined,
      addGroup: false,
      blockSize: undefined,
    }
  );
  const emit = defineEmits<{
    (event: 'retry'): void;
  }>();

  const content = ref<HTMLElement>();
  const errorRegion = ref<HTMLElement>();
  const retrying = ref(false);

  const showPlaceholder = computed(
    () => !props.hasHtml && props.status === 'loading'
  );
  const showError = computed(() => props.status === 'error' || retrying.value);
  const sections = computed(() => props.groups ?? [props.chips]);

  function retry(): void {
    if (retrying.value) {
      return;
    }

    retrying.value = true;
    emit('retry');
  }

  // Retry stays mounted while it loads, so keyboard focus isn't lost. Once the
  // field loads, focus moves into it, unless the user has moved focus elsewhere.
  watch(
    () => props.status,
    async (status) => {
      if (!retrying.value || status === 'loading') {
        return;
      }

      const retryHadFocus =
        errorRegion.value?.contains(document.activeElement) ?? false;
      retrying.value = false;

      if (status !== 'loaded' || !retryHadFocus || !content.value) {
        return;
      }

      await nextTick();
      const target = firstFocusableWithin(content.value);
      if (target) {
        target.focus();
      } else {
        content.value.tabIndex = -1;
        content.value.focus();
      }
    }
  );
</script>

<template>
  <div>
    <div
      v-if="showPlaceholder"
      class="server-rendered-control-placeholder"
      aria-hidden="true"
      data-server-rendered-control-placeholder
    >
      <div
        v-if="blockSize"
        class="server-rendered-control-placeholder__block"
        :style="{blockSize}"
      ></div>
      <div
        v-else
        :class="{'server-rendered-control-placeholder__groups': groups}"
      >
        <div
          v-for="(sectionChips, sectionIndex) in sections"
          :key="sectionIndex"
          :class="{'server-rendered-control-placeholder__group': groups}"
        >
          <div
            v-if="groups"
            class="server-rendered-control-placeholder__block server-rendered-control-placeholder__title"
          ></div>
          <ul
            v-if="sectionChips || groups"
            class="server-rendered-control-placeholder__chips"
          >
            <li
              v-for="index in sectionChips"
              :key="index"
              class="server-rendered-control-placeholder__block server-rendered-control-placeholder__chip"
            ></li>
          </ul>
          <div
            v-if="buttons"
            class="server-rendered-control-placeholder__footer"
            :class="{
              'server-rendered-control-placeholder__footer--after-chips':
                sectionChips && !groups,
            }"
          >
            <div
              v-for="index in buttons"
              :key="index"
              class="server-rendered-control-placeholder__block server-rendered-control-placeholder__button"
            ></div>
          </div>
        </div>
      </div>
      <div
        v-if="addGroup"
        class="server-rendered-control-placeholder__block server-rendered-control-placeholder__button"
      ></div>
    </div>
    <div ref="content">
      <slot />
    </div>
    <div ref="errorRegion" role="alert">
      <div v-if="showError" class="server-rendered-control-error">
        <p>{{ t('Couldn’t load this field.') }}</p>
        <craft-button
          type="button"
          size="small"
          variant="outline"
          .loading="retrying"
          @click="retry"
        >
          {{ t('Retry') }}
        </craft-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
  .server-rendered-control-placeholder__groups {
    display: flex;
    flex-wrap: wrap;
    align-items: start;
    gap: var(--c-spacing-md);
    margin-block-end: var(--c-spacing-md);
  }

  .server-rendered-control-placeholder__group {
    padding: var(--c-spacing-md);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-pane-radius);

    .server-rendered-control-placeholder__chips {
      min-block-size: var(--c-size-control-md);
    }
  }

  .server-rendered-control-placeholder__title {
    inline-size: 6rem;
    margin-block-end: var(--c-spacing-md);
  }

  .server-rendered-control-placeholder__chips {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .server-rendered-control-placeholder__footer {
    display: flex;
    gap: var(--c-spacing-sm);
    padding-block-start: var(--c-spacing-md);
  }

  .server-rendered-control-placeholder__footer--after-chips {
    margin-block-start: var(--c-spacing-md);
  }

  .server-rendered-control-placeholder__block {
    block-size: var(--c-size-control-md);
    max-inline-size: 100%;
    border: 1px solid transparent;
    border-radius: var(--c-radius-md);
    background-color: var(--c-surface-sunken);
    animation: var(--animate-pulse);
  }

  .server-rendered-control-placeholder__chip {
    inline-size: 14rem;
  }

  .server-rendered-control-placeholder__button {
    inline-size: 6rem;
  }

  .server-rendered-control-error {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-md);
    color: var(--c-text-quiet);
  }

  .server-rendered-control-error p {
    margin: 0;
  }

  @media (prefers-reduced-motion: reduce) {
    .server-rendered-control-placeholder__block {
      animation: none;
    }
  }
</style>
