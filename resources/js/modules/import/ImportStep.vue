<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import type {UiPayload} from '@/modules/ui/types';
  import type {UrlMethodPair} from '@inertiajs/core';
  import type {FormAction} from '@/common/types';
  import {ref} from 'vue';

  type ViewModel = CraftCms.Cms.Http.ViewModels.ImportPlanEditViewModel;

  const props = defineProps<{
    importerTypes: ViewModel['importerTypes'];
    index: number;
    step: any;
  }>();

  const active = ref(true);

  function typeLabel(step: ImportStep): string {
    return (
      props.importerTypes.find((type) => type.value === step.type)?.label ??
      t('Choose an importer')
    );
  }

  function toggle() {
    if (active.value) {
      active.value = false;
    } else {
      active.value = true;
    }
  }
</script>

<template>
  <div class="border border-quiet rounded-lg">
    <div class="flex items-center justify-between gap-md p-lg">
      <div
        class="aspect-square border border-quiet rounded-full w-6 grid place-items-center font-mono text-xs text-quiet"
      >
        {{ index + 1 }}
      </div>
      <div class="flex items-baseline gap-lg">
        <div class="text-lg font-bold">{{ typeLabel(step) }}</div>
        <pre class="text-xs text-quiet">{{ step.source }}</pre>
      </div>
      <div class="flex-1"></div>
      <div>
        <craft-button
          icon="chevron-down"
          variant="plain"
          :aria-label="t('Expand step')"
          @click="toggle"
        ></craft-button>
      </div>
    </div>

    <template v-if="active">
      <hr />
      <craft-field-group class="p-lg">
        <craft-field
          :label="t('Data Source')"
          required
          :tip="t('Type ./ to browse the project folder, or @ for an alias.')"
          :help-text="
            t(
              'The aliased or @root-relative path to a file or a URL containing the data you want this step to import.'
            )
          "
        >
          <craft-input slot="input" value="hey"></craft-input>
        </craft-field>
      </craft-field-group>
    </template>
  </div>
</template>

<style scoped lang="scss"></style>
