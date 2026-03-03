<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import AppLayout from '@/layout/AppLayout.vue';
  import {computed, ref, useSlots, watch} from 'vue';
  import {useMediaQuery} from '@vueuse/core';

  defineProps<{
    title?: string;
    pageTitle?: string;
    debug?: any;
  }>();
  const slots = useSlots();

  const isLarge = useMediaQuery('(min-width: 768px)');
  const navState = ref<'expanded' | 'collapsed'>('expanded');
  const forwardedSlots = computed(() => {
    const {default: _, ...rest} = slots;
    return rest;
  });

  const toggleLabel = computed(() =>
    navState.value === 'expanded' ? t('Hide sidebar') : t('Show sidebar')
  );

  function toggleNav() {
    navState.value = navState.value === 'expanded' ? 'collapsed' : 'expanded';
  }

  const skipLinks = [
    {label: t('Skip to secondary navigation'), url: '#secondary-nav'},
  ];

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
  <AppLayout :full-width="true" :title="title" :debug="debug" :additional-skip-links="skipLinks">
    <!-- Forward all other slots -->
    <template v-for="(_, name) in forwardedSlots" #[name]="slotData">
      <slot :name="name" v-bind="slotData || {}"></slot>
    </template>

    <div class="index-grid">
      <nav id="secondary-nav" :aria-label="t('Secondary')" tabindex="-1">
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
        <div v-if="navState === 'expanded'" id="nav-container">
          <slot name="interior-nav" :state="navState"></slot>
        </div>
      </nav>
      <div
        class="bg-white border border-border-subtle rounded-sm shadow-sm @container"
      >
        <slot></slot>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped lang="scss">
  #nav-container {
    background-color: color-mix(var(--color-slate-900), trans);
  }
</style>
