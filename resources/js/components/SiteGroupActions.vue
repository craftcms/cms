<script setup lang="ts">
  import {t} from '@craftcms/cp';

  const emit = defineEmits<{
    (e: 'click:rename'): void;
    (e: 'click:delete'): void;
  }>();
</script>

<template>
  <craft-action-menu>
    <craft-button type="button" icon size="small" slot="invoker">
      <craft-icon name="gear" :label="t('Site group Actions')"></craft-icon>
    </craft-button>

    <div slot="content">
      <craft-action-item @click.prevent="emit('click:rename')">
        {{ t('Rename Group') }}
      </craft-action-item>
      <craft-action-item variant="danger" @click.prevent="emit('click:delete')">
        {{ t('Delete Group') }}
      </craft-action-item>
    </div>
  </craft-action-menu>
</template>

<style scoped lang="scss"></style>
