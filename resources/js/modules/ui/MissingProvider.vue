<script setup lang="ts">
  import '@craftcms/ui/components/missing-component/missing-component';
  import {computed} from 'vue';
  import type {UiControlPayload, UiNodePayload} from './types';

  defineOptions({inheritAttrs: false});

  type MissingProviderProps = {
    error: string;
    pluginName?: string | null;
    action?: {
      label: string;
      url: string;
      method: 'get' | 'post';
    } | null;
  };

  const props = defineProps<{
    node?: UiNodePayload<MissingProviderProps>;
    control?: UiControlPayload<MissingProviderProps>;
  }>();
  const presentation = computed(() => (props.node ?? props.control)!.props);
</script>

<template>
  <craft-missing-component
    :slot="$attrs.slot"
    :error="presentation.error"
    :plugin-name="presentation.pluginName"
  >
    <button
      v-if="presentation.action?.method === 'post'"
      slot="action"
      type="submit"
      class="btn"
      :formaction="presentation.action.url"
    >
      {{ presentation.action.label }}
    </button>
    <a
      v-else-if="presentation.action"
      slot="action"
      class="btn"
      :href="presentation.action.url"
    >
      {{ presentation.action.label }}
    </a>
  </craft-missing-component>
</template>
