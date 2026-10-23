<script setup lang="ts">
  /**
   * A card in an “Ask AI” reply, about one field or the content as a whole:
   * its label, which opens and closes the card, then a badge and actions on
   * the other side of the header. Collapsing leaves just the header.
   */
  import {useTemplateRef} from 'vue';

  defineProps<{
    label: string;
    collapsed: boolean;
    badge?: string | null;
    badgeFill?: 'neutral' | 'success' | 'warning';
  }>();

  const emit = defineEmits<{
    toggle: [];
  }>();

  const toggleButton = useTemplateRef<HTMLElement>('toggleButton');

  defineExpose({focus: () => toggleButton.value?.focus()});
</script>

<template>
  <craft-card padding="md" .collapsed="collapsed">
    <craft-button
      ref="toggleButton"
      slot="label"
      type="button"
      variant="plain"
      size="small"
      flush="inline"
      class="ask-ai-card__toggle"
      :icon="collapsed ? 'chevron-right' : 'chevron-down'"
      :aria-expanded="collapsed ? 'false' : 'true'"
      @click="emit('toggle')"
    >
      {{ label }}
    </craft-button>

    <craft-badge
      v-if="badge"
      slot="badge"
      :fill="badgeFill ?? 'neutral'"
      size="small"
      no-prefix
    >
      {{ badge }}
    </craft-badge>

    <div v-if="$slots.actions" slot="actions" class="flex items-center gap-sm">
      <slot name="actions" />
    </div>

    <slot />

    <div v-if="$slots.footer" slot="footer">
      <slot name="footer" />
    </div>
  </craft-card>
</template>

<style scoped>
  .ask-ai-card__toggle::part(label) {
    font-weight: 600;
  }
</style>
