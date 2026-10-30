<script setup lang="ts">
  /**
   * Links to the element on the front end, in a new window. The URLs arrive
   * ready to follow: a live element points at its own URL, anything else at a
   * token-minting redirect that lands on the tokenized preview.
   */
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItemLink} from '@/common/types';
  import type {ElementPreviewTarget} from '@/modules/elements/composables/useElementEditor';

  const props = defineProps<{
    targets: Array<ElementPreviewTarget>;
  }>();

  const actions = computed<ActionItemLink[]>(() =>
    props.targets.map((target) => ({
      type: 'link',
      href: target.url,
      label: target.label,
      target: '_blank',
    }))
  );
</script>

<template>
  <ActionMenu
    v-if="targets.length > 1"
    :actions="actions"
    :label="t('View')"
    :flush="false"
  >
    <template #invoker="{attributes}">
      <craft-button
        v-bind="attributes"
        type="button"
        variant="plain"
        size="small"
        icon="chevron-down"
      >
        {{ t('View') }}
      </craft-button>
    </template>
  </ActionMenu>
  <craft-button
    v-else-if="targets.length"
    :href="targets[0]!.url"
    target="_blank"
    variant="plain"
    size="small"
    icon="arrow-up-right-from-square"
    icon-position="suffix"
  >
    {{ t('View') }}
    <span class="sr-only">{{ t('Opens in a new window') }}</span>
  </craft-button>
</template>
