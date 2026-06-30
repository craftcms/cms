<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {usePage} from '@inertiajs/vue3';
  import AppLayout, {type AppLayoutProps} from '@/common/layouts/AppLayout.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import type {FormSaveOptions} from '@/common/types';
  import {computed, ref, useSlots, watch} from 'vue';
  import {useMediaQuery} from '@vueuse/core';

  const props = defineProps<AppLayoutProps & {}>();
  const slots = useSlots();

  const emit = defineEmits<{
    (e: 'save', options?: FormSaveOptions): void;
  }>();

  const page = usePage<{
    subnav?: Array<CraftCms.Cms.Cp.Data.NavItem>;
  }>();

  const layoutSlotNames = ['default', 'interior-nav', 'subnav-actions'];
  const isLarge = useMediaQuery('(min-width: 768px)');
  const navState = ref<'expanded' | 'collapsed'>('expanded');
  const subnav = computed(() => page.props.subnav ?? []);
  const forwardedSlots = computed(() =>
    Object.fromEntries(
      Object.entries(slots).filter(([name]) => !layoutSlotNames.includes(name))
    )
  );

  const toggleLabel = computed(() =>
    navState.value === 'expanded' ? t('Hide sidebar') : t('Show sidebar')
  );

  function toggleNav() {
    navState.value = navState.value === 'expanded' ? 'collapsed' : 'expanded';
  }

  const skipLinks = [
    {label: t('Skip to secondary navigation'), url: '#secondary-nav'},
    {label: t('Skip to content'), url: '#content-pane'},
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
  <AppLayout
    :full-width="true"
    :title="title"
    :debug="debug"
    :form="props.form"
    :default-form-actions="props.defaultFormActions"
    :form-actions="props.formActions"
    :form-additional-actions="props.formAdditionalActions"
    :additional-skip-links="skipLinks"
    @save="(options) => emit('save', options)"
  >
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
          <slot name="interior-nav" :state="navState">
            <craft-nav-list v-if="subnav.length">
              <template v-for="(item, index) in subnav" :key="index">
                <CpLink
                  as="craft-nav-item"
                  :active="item.selected"
                  :href="item.url"
                  block
                  flush
                >
                  {{ item.label }}
                </CpLink>
              </template>
            </craft-nav-list>
          </slot>
          <slot name="subnav-actions" :state="navState"></slot>
        </div>
      </nav>
      <div
        id="content-pane"
        class="bg-white border border-neutral-border-quiet rounded-sm shadow-sm @container"
        tabindex="-1"
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
