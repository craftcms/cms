<script setup lang="ts">
  /**
   * A plugin widget on the core Dashboard. The Dashboard isn't the plugin's
   * page, so its `craft:layout-slot` renders nothing and Craft's own actions
   * are left alone.
   */
  import {resolveComponent} from 'vue';

  const LayoutSlot = resolveComponent('craft:layout-slot');
</script>

<template>
  <p>
    This widget tries to put a button next to <strong>New widget</strong> at
    the top of the Dashboard. It doesn't appear: plugins can only fill layout
    slots on their own pages. The console says why.
  </p>

  <LayoutSlot name="content-actions">
    <craft-button type="button" icon="flask">From a widget</craft-button>
  </LayoutSlot>
</template>
