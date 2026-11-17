<script setup lang="ts">
  /**
   * Offers to widen a whole selected page to every result the index lists, or
   * to narrow every result back to the page on screen.
   */
  import {computed} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';

  const props = defineProps<{
    /** `all` offers every result; `page` offers just the page on screen. */
    offer: 'all' | 'page';
    /** How many results are on the page on screen. */
    pageCount: number;
    /** How many results the index lists. */
    total: number;
  }>();

  const emit = defineEmits<{
    'select-all': [];
    'select-page': [];
  }>();

  const message = computed(() =>
    props.offer === 'all'
      ? t(
          '{count, plural, =1{The result on this page is selected.} other{All # results on this page are selected.}}',
          {count: props.pageCount}
        )
      : t('All {total, number} results are selected.', {total: props.total})
  );
</script>

<template>
  <div class="select-all-banner">
    <span>{{ message }}</span>
    <craft-button
      type="button"
      size="small"
      :variant="ButtonVariant.Plain"
      @click="offer === 'all' ? emit('select-all') : emit('select-page')"
      >{{
        offer === 'all'
          ? t('Select all {total, number} results', {total})
          : t('Select only this page')
      }}</craft-button
    >
  </div>
</template>

<style scoped lang="scss">
  .select-all-banner {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-sm);
  }
</style>
