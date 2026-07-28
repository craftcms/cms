<script setup lang="ts">
  import ConfigSyncController from '@actions/ConfigSyncController';
  import {t} from '@craftcms/ui/utilities/translate';
  import {Form} from '@inertiajs/vue3';

  withDefaults(
    defineProps<{
      force?: boolean;
      returnTo?: string;
      label?: string;
    }>(),
    {
      force: false,
      label: t('Reapply everything'),
      returnTo: 'utilities/project-config',
    }
  );
</script>

<template>
  <Form :action="ConfigSyncController.index()" v-slot="{processing}">
    <input type="hidden" name="return" :value="returnTo" />
    <input type="hidden" name="force" value="1" v-if="force" />
    <craft-button
      type="submit"
      variant="accent"
      :loading="processing"
      v-bind="$attrs"
    >
      <slot name="label">
        {{ label }}
      </slot>
    </craft-button>
  </Form>
</template>

<style scoped lang="scss"></style>
