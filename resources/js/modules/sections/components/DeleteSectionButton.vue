<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import type {SectionModel} from '@/pages/settings/sections/Index.vue';
  import {useForm} from '@inertiajs/vue3';
  import {destroy} from '@actions/Settings/SectionsController';

  const props = defineProps<{
    section: SectionModel;
  }>();

  const form = useForm({});

  function handleDelete() {
    if (
      !confirm(
        t('Are you sure you want to delete “{name}” and all its entries?', {
          name: props.section.name,
        })
      )
    ) {
      return;
    }

    form.submit(destroy({section: props.section.id}));
  }
</script>

<template>
  <form @submit.prevent="handleDelete" method="post">
    <craft-button
      variant="danger-plain"
      type="submit"
      size="small"
      icon
      :loading="form.processing"
    >
      <craft-icon :label="t('Delete section')" name="x"></craft-icon>
    </craft-button>
  </form>
</template>

<style scoped lang="scss"></style>
