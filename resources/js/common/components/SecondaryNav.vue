<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, ref, watch} from 'vue';
  import {useMediaQuery} from '@vueuse/core';

  const {items = []} = defineProps<{
    items?: Array<CraftCms.Cms.Cp.Data.NavItem>;
  }>();

  const isLarge = useMediaQuery('(min-width: 768px)');
  const navState = ref<'expanded' | 'collapsed'>('expanded');

  const toggleLabel = computed(() =>
    navState.value === 'expanded' ? t('Hide sidebar') : t('Show sidebar')
  );

  function toggleNav() {
    navState.value = navState.value === 'expanded' ? 'collapsed' : 'expanded';
  }

  watch(
    isLarge,
    (newValue) => {
      /**
       * When transitioning from small to large or large to small make sure
       * we set the nav state accordingly
       */
      navState.value = newValue ? 'expanded' : 'collapsed';
    },
    {immediate: true}
  );
</script>

<template>
  <nav :aria-label="t('Secondary')">
    <craft-button
      v-if="!isLarge"
      type="button"
      aria-controls="nav-container"
      :aria-expanded="navState === 'expanded'"
      @click="toggleNav"
      align="start"
      class="text-sm py-0 min-h-0"
    >
      <craft-icon
        slot="suffix"
        :name="navState === 'expanded' ? 'chevron-up' : 'chevron-down'"
        :style="{
          fontSize: '0.8em',
          position: 'relative',
          insetBlockStart: navState === 'expanded' ? '1px' : 0,
        }"
      ></craft-icon>
      {{ toggleLabel }}
    </craft-button>
    <!-- v-show, not v-if: hosts the subnav-actions teleport target, which
      must stay in the DOM while collapsed. -->
    <div v-show="navState === 'expanded'" id="nav-container">
      <slot>
        <craft-nav-list v-if="items.length">
          <template v-for="(item, index) in items" :key="index">
            <template v-if="item.subnav">
              <craft-nav-item
                initial-state="open"
                block
                flush
                :group="item.group"
              >
                <span class="text-xs font-bold">{{ item.label }}</span>

                <craft-nav-list slot="subnav">
                  <craft-nav-item
                    v-for="(subitem, subindex) in item.subnav"
                    :key="subindex"
                    :active="subitem.selected"
                    :href="subitem.url"
                    block
                    flush
                  >
                    {{ subitem.label }}
                  </craft-nav-item>
                </craft-nav-list>
              </craft-nav-item>
            </template>

            <template v-else>
              <craft-nav-item
                :active="item.selected"
                :href="item.url"
                block
                flush
              >
                {{ item.label }}
              </craft-nav-item>
            </template>
          </template>
        </craft-nav-list>
      </slot>
      <slot name="actions"></slot>
    </div>
  </nav>
</template>

<style scoped lang="css">
  .nav-heading {
    display: grid;
    gap: var(--c-spacing-sm);
    margin: 0;
  }

  .nav-heading-label {
    display: block;
    padding-block: var(--c-spacing-sm);
    color: var(--c-text-quiet);
    font-weight: 600;
  }
</style>
