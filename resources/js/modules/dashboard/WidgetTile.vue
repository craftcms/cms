<script setup lang="ts">
  import {computed} from 'vue';
  import TileContent from './TileContent.vue';
  import {isTintedColor, useWidgetColor} from './useWidgetColor';

  /**
   * A large button filling a dashboard widget's body, with an icon, label,
   * and description, such as the New Entry widget's.
   */
  const props = defineProps<{
    icon?: string | null;
    label: string;
    description?: string | null;
    /**
     * The tile's color, such as an entry type's. A widget with a tint of its
     * own colors the tile with it instead, as it does the rest of the widget.
     */
    color?: string | null;
    loading?: boolean;
    disabled?: boolean;
  }>();

  const emit = defineEmits<{click: [event: MouseEvent]}>();

  const widgetColor = useWidgetColor();
  const tileColor = computed(() =>
    isTintedColor(widgetColor.value) ? undefined : (props.color ?? undefined)
  );
</script>

<template>
  <craft-button
    type="button"
    variant="fill"
    class="widget-tile"
    :data-color="tileColor"
    .loading="loading"
    .disabled="disabled || loading"
    @click="emit('click', $event)"
  >
    <TileContent :icon="icon" :label="label" :description="description" />
  </craft-button>
</template>

<style scoped>
  .widget-tile {
    --_tile-fill: color-mix(
      in oklab,
      var(--c-color-fill-loud, var(--c-color-neutral-fill-loud)) 10%,
      transparent
    );
    display: block;
    inline-size: 100%;
    block-size: auto;
    padding-block: calc(var(--c-spacing) * 5);
    border-radius: var(--c-radius-md);
    background-color: var(--_tile-fill);
    color: var(--c-color-on-quiet, var(--c-text-default));

    &:hover {
      background-color: color-mix(in oklab, var(--_tile-fill), currentColor 5%);
    }

    &:active {
      background-color: color-mix(
        in oklab,
        var(--_tile-fill),
        currentColor 10%
      );
    }
  }
</style>
