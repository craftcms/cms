<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {ref} from 'vue';
  import {dismissTip, isTipDismissed} from './dismissedTips';
  import type {FormNodePayload} from './types';

  const props = defineProps<{
    node: FormNodePayload<{
      html: string;
      variant: string;
      appearance?: string;
      icon?: string;
      dismissible: boolean;
      width: number;
    }>;
  }>();

  // A dismissed tip stays dismissed across loads, keyed by layout element UID.
  const dismissed = ref(
    props.node.props.dismissible && isTipDismissed(props.node.uid)
  );

  function dismiss(): void {
    if (props.node.uid) {
      dismissTip(props.node.uid);
    }

    dismissed.value = true;
  }
</script>

<template>
  <craft-callout
    v-if="!dismissed"
    :class="`width-${node.props.width}`"
    :data-form-node="node.uid"
    :variant="node.props.variant"
    v-bind="node.props.appearance ? {appearance: node.props.appearance} : {}"
    :icon="node.props.icon"
    :data-dismissible="node.props.dismissible || undefined"
  >
    <span v-html="node.props.html"></span>

    <craft-button
      v-if="node.props.dismissible"
      slot="action"
      type="button"
      appearance="plain"
      size="small"
      icon="xmark"
      :aria-label="t('Dismiss')"
      @click="dismiss"
    ></craft-button>
  </craft-callout>
</template>
