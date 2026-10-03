<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed, ref} from 'vue';
  import Select from '@/common/form/Select.vue';

  const props = defineProps<{
    currentPage: number;
    lastPage: number;
    loading?: boolean;
  }>();

  const emit = defineEmits<{
    (e: 'move', page: number): void;
  }>();

  interface PopoverElement extends HTMLElement {
    hide(): Promise<void>;
  }

  const popover = ref<PopoverElement>();
  const targetPageRaw = ref<string | number>(props.currentPage);

  // Native select values are strings, but the page number is numeric.
  const targetPage = computed(() => parseInt(String(targetPageRaw.value), 10));

  const pages = Array.from({length: props.lastPage}, (_, i) => i + 1);

  function handleMove(): void {
    if (targetPage.value === props.currentPage) {
      return;
    }

    emit('move', targetPage.value);
    popover.value?.hide();
  }

  function onOpenedChanged(event: CustomEvent<boolean>): void {
    if (event.detail) {
      targetPageRaw.value = props.currentPage;
    }
  }
</script>

<template>
  <craft-popover
    ref="popover"
    placement="bottom-end"
    @opened-changed="onOpenedChanged"
  >
    <!-- The popover relocates its invoker; v-once prevents Vue patching the moved DOM.
      Styled to match `craft-action-item`'s row rather than actually being one:
      `craft-action-menu` finds every `craft-action-item` in its content — including one
      nested this deep — and closes itself on click, which would dismiss the menu before
      this popover ever opened. -->
    <button type="button" class="move-to-page-invoker" slot="invoker" v-once>
      <span>{{ t('Move to page…') }}</span>
    </button>

    <div slot="content-body" class="flex flex-nowrap items-end gap-2">
      <Select
        :label="t('Choose a page')"
        v-model="targetPageRaw"
        :options="pages.map((p) => ({label: String(p), value: String(p)}))"
      />
      <craft-button
        type="button"
        variant="primary"
        size="small"
        :loading="loading"
        @click="handleMove"
        >{{ t('Move') }}</craft-button
      >
    </div>
  </craft-popover>
</template>

<style scoped>
  /* Mirrors `craft-action-item`'s own `.action-item`/`.action-item__label` styling
     (`packages/craftcms-ui/src/components/action-item/action-item.styles.ts`), since this
     can't be that element itself — see the template comment above. */
  .move-to-page-invoker {
    all: unset;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    width: 100%;
    color: var(--c-color-on-quiet, inherit);
    padding-inline: var(--c-spacing-sm);
    padding-block: var(--c-spacing-sm);
    border-radius: var(--c-radius-md);
    cursor: pointer;
  }

  .move-to-page-invoker span {
    margin-inline: var(--c-spacing-sm);
  }

  @media (hover: hover) {
    .move-to-page-invoker:hover {
      background-color: var(
        --c-color-fill-quiet,
        var(--c-color-neutral-fill-quiet)
      );
      color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
    }
  }
</style>
