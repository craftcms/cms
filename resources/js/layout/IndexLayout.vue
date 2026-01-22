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

  watch(isLarge, (newValue) => {
    /**
     * When transitioning from small to large or large to small make sure
     * we set the nav state accordingly
     */
    navState.value = newValue ? 'expanded' : 'collapsed';
  });
</script>

<template>
  <AppLayout :full-width="true" :title="title" :debug="debug">
    <!-- Forward all other slots -->
    <template v-for="(_, name) in forwardedSlots" #[name]="slotData">
      <slot :name="name" v-bind="slotData || {}"></slot>
    </template>

    <div class="interior">
      <div class="">
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
        <Transition>
          <div v-if="navState === 'expanded'" id="nav-container">
            <slot name="interior-nav" :state="navState"></slot>
          </div>
        </Transition>
      </div>
      <div
        class="bg-white border border-border-subtle rounded-sm shadow-sm overflow-auto"
      >
        <slot></slot>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped lang="scss">
  .interior {
    display: grid;
    gap: var(--c-spacing-md);

    @container (width >= 768px) {
      grid-template-columns:
        clamp(calc(120rem / 16), 20%, calc(180rem / 16))
        6fr;
      align-items: start;
    }
  }

  #nav-container {
    background-color: color-mix(var(--color-slate-900), trans);
  }
</style>
