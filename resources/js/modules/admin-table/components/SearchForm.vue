<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {type FormComponentProps} from '@inertiajs/core';
  import {Form} from '@inertiajs/vue3';
  import {useDebounceFn} from '@vueuse/core';
  import CraftInputSearch from '@craftcms/ui/vue/CraftInputSearch.vue';

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
    class="w-full"
    :options="{
      preserveScroll: true,
      preserveState: true,
      only: ['data', 'searchTerm'],
      ...options,
    }"
  >
    <div class="flex gap-2 items-start">
      <CraftInputSearch
        class="flex-1"
        name="search"
        :label="t('Search Term')"
        v-model="searchTerm"
        @input="autoSearch(submit)"
      />
      <craft-button type="submit" .loading="processing" slot="suffix">{{
        t('Search')
      }}</craft-button>
    </div>
  </Form>
</template>

<style scoped lang="scss"></style>
