<script setup lang="ts">
  import {usePage} from '@inertiajs/vue3';
  import IndexLayout from '@/common/layouts/IndexLayout.vue';

  defineOptions({
    inheritAttrs: false,
  });

  interface AddressesPageProps extends Record<string, unknown> {
    content?: string | null;
    details?: string | null;
  }

  const props = usePage<AddressesPageProps>().props;
</script>

<template>
  <IndexLayout>
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div v-if="props.content" class="p-4" v-html="props.content" />

    <template v-if="props.details" #details>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div v-html="props.details" />
    </template>
  </IndexLayout>
</template>
