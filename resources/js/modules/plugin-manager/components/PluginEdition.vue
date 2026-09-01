<script setup lang="ts">
  import {t, capitalize} from '@craftcms/ui';
  import {computed} from 'vue';

  const props = withDefaults(
    defineProps<{
      url?: string | null;
      edition?: string | null;
      isTrial?: boolean;
    }>(),
    {url: null, edition: null, isTrial: false}
  );

  const editionName = computed(() =>
    props.edition ? capitalize(props.edition) : null
  );
</script>

<template>
  <component
    :is="url ? 'a' : 'div'"
    :href="url"
    class="cp-plugin-edition"
    data-color="neutral"
  >
    <div v-if="edition" class="cp-plugin-edition__name">
      {{ editionName }}
    </div>
    <div v-if="isTrial" class="cp-plugin-edition__trial">
      {{ t('Trial') }}
    </div>
  </component>
</template>

<style scoped lang="scss">
  .cp-plugin-edition {
    text-decoration: none;
    display: flex;
    border: 1px solid var(--c-color-border-loud);
    background-color: var(--c-color-fill-loud);
    color: var(--c-color-on-quiet);
    border-radius: var(--c-radius-sm);
    align-items: center;
    overflow: clip;

    @media (hover: hover) {
      &:hover {
        --c-color-fill-quiet: var(--c-color-accent-fill-quiet);
        --c-color-border-quiet: var(--c-color-accent-border-quiet);
        --c-color-on-quiet: var(--c-color-accent-on-quiet);
        --c-color-fill-normal: var(--c-color-accent-fill-normal);
        --c-color-border-normal: var(--c-color-accent-border-normal);
        --c-color-on-normal: var(--c-color-accent-on-normal);
        --c-color-fill-loud: var(--c-color-accent-fill-loud);
        --c-color-border-loud: var(--c-color-accent-border-loud);
        --c-color-on-loud: var(--c-color-accent-on-loud);
      }

      //background-color: var(--c-color-fill-normal);
      //border-color: var(--c-color-border-normal);
      //color: var(--c-color-on-quiet);
    }
  }

  .cp-plugin-edition__name {
    background-color: var(--c-color-fill-quiet);
  }

  .cp-plugin-edition__name,
  .cp-plugin-edition__trial {
    padding: 0 calc(var(--c-spacing) * 1.5);
  }

  .cp-plugin-edition__trial {
    background-color: var(--c-color-fill-loud);
    color: var(--c-color-on-loud);
  }
</style>
