<script setup lang="ts">
  /**
   * A destination column's field type icon, name and handle, badged when rows are
   * matched on it or when that's all it can be used for.
   */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/icon/icon';
  import '@craftcms/ui/components/info-icon/info-icon';
  import {Color, t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {isMatchKey} from '../matching';
  import {useMappingContext, type MappingColumn} from '../types';

  const {col} = defineProps<{col: MappingColumn}>();

  const context = useMappingContext();

  const isMatchOnly = computed(
    () => !col.isContainer && col.canBeSet === false
  );

  const matched = computed(() => isMatchKey(context.values, col));
</script>

<template>
  <!-- the icon hangs to the left, so the label and handle line up with each other -->
  <div class="destination-cell">
    <span class="destination-cell__icon" aria-hidden="true">
      <craft-icon v-if="col.icon" :name="col.icon"></craft-icon>
    </span>
    <div class="destination-cell__text">
      <div class="destination-cell__label">
        <div>
          {{ col.label }}
        </div>
        <span v-if="matched || isMatchOnly" class="destination-cell__badges">
          <craft-badge
            v-if="matched"
            size="small"
            :fill="Color.Accent"
            no-prefix
            >{{ t('Match Key') }}</craft-badge
          >
          <template v-else>
            <craft-badge size="small" fill="gray" no-prefix>{{
              t('Match only')
            }}</craft-badge>
            <craft-info-icon>{{
              t(
                'This value can only be used to match against an existing element.'
              )
            }}</craft-info-icon>
          </template>
        </span>
      </div>
      <pre class="text-quiet font-normal">{{ col.handle }}</pre>
    </div>
  </div>
</template>

<style scoped>
  /* registered, so `1lh` resolves against the cell's line height, not the label's */
  @property --destination-cell-line {
    syntax: '<length>';
    inherits: true;
    initial-value: 0px;
  }

  .destination-cell {
    --destination-cell-line: 1lh;
    display: grid;
    grid-template-columns: 1.25em minmax(0, 1fr);
    column-gap: var(--c-spacing-sm);
    align-items: start;
  }

  /* a fixed-width slot, centering glyphs of any width, level with the label's first line */
  .destination-cell__icon {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    block-size: 1lh;
    color: var(--c-text-quiet);
    font-weight: normal;
  }

  /* tighter lines for a name that wraps, with the first still centered on the cell's line, level with the controls */
  .destination-cell__label {
    display: flex;
    align-items: start;
    gap: var(--c-spacing-sm);
    margin-block-start: calc((var(--destination-cell-line) - 1lh) / 2);
    line-height: 1.25;
  }

  /* one line tall and level with the name's first line, so a badge taller than the text can't push it down */
  .destination-cell__badges {
    display: flex;
    flex: none;
    align-items: center;
    gap: var(--c-spacing-sm);
    block-size: 1lh;
  }

  .destination-cell__text {
    min-width: 0;
  }
</style>
