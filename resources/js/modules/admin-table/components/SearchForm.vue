<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {type FormComponentProps} from '@inertiajs/core';
  import {Form} from '@inertiajs/vue3';
  import {useDebounceFn} from '@vueuse/core';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';

  const searchTerm = defineModel<string>();
  defineProps<FormComponentProps & {}>();

  const autoSearch = useDebounceFn((submit: () => void) => {
    submit();
  }, 500);
</script>

<template>
  <Form
    :action="action"
    v-slot="{processing, submit}"
    class="cp:w-full"
    :options="{
      preserveScroll: true,
      preserveState: true,
      only: ['data', 'searchTerm'],
      ...options,
    }"
  >
    <div class="cp:flex cp:gap-2 cp:items-start">
      <CraftInput
        class="cp:flex-1"
        name="search"
        :label="t('Search term')"
        v-model="searchTerm"
        @input="autoSearch(submit)"
        label-sr-only
      />
      <craft-button type="submit" :loading="processing" slot="suffix">{{
        t('Search')
      }}</craft-button>
    </div>
  </Form>
</template>

<style scoped lang="scss"></style>
