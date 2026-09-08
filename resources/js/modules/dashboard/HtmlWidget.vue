<script setup lang="ts">
  import {inject, ref} from 'vue';
  import {t} from '@craftcms/ui';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import {renderHtmlWidget, type RenderHtmlWidget} from './htmlWidgets';
  import type {DashboardWidget} from './types';

  defineProps<{widget: DashboardWidget}>();
  const emit = defineEmits<{ready: []}>();
  const renderWidget = inject(renderHtmlWidget)!;
  const error = ref(false);

  const render: RenderHtmlWidget = async (...args) => {
    error.value = false;

    try {
      return await renderWidget(...args);
    } catch {
      error.value = true;
    }
  };
</script>

<template>
  <craft-pane appearance="raised" padding="lg">
    <slot name="header" />
    <craft-spinner class="body-loading" visible role="status">{{
      t('Loading…')
    }}</craft-spinner>
    <div class="body">
      <craft-callout v-if="error" role="alert" variant="danger">
        {{ t('A server error occurred.') }}
      </craft-callout>
      <HtmlFragmentRenderer
        :fragment="widget.fragment"
        :render="render"
        @ready="emit('ready')"
      />
    </div>
  </craft-pane>
</template>

<style scoped>
  .body-loading {
    display: none;
  }
  .loading .body-loading {
    display: block;
  }
</style>
