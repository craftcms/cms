<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {create} from '@actions/Settings/EntryTypesController';
  import {useTemplateRef} from 'vue';

  const emit = defineEmits<{
    (e: 'success'): void;
  }>();

  const invoker = useTemplateRef('invoker');

  function createSlideout() {
    const slideout = new Craft.CpScreenSlideout(
      create['/admin/settings/entry-types/new']().url
    );

    slideout.on('submit', () => {
      emit('success');
    });

    slideout.on('close', () => {
      invoker.value?.focus();
    });
  }

  function handleClick() {
    createSlideout();
  }
</script>

<template>
  <craft-button
    type="button"
    appearance="filled"
    @click="handleClick"
    ref="invoker"
  >
    <craft-icon name="plus" slot="prefix"></craft-icon>
    {{ t('Create') }}
  </craft-button>
</template>

<style scoped lang="scss"></style>
