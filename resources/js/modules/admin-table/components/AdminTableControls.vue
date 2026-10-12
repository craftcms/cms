<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftSelectRich, {
    type SelectRichOption,
  } from '@craftcms/ui/vue/CraftSelectRich.vue';
  import type {CheckboxOption} from '@/common/types';
  import ElementStatus from '@/modules/elements/ElementStatus.vue';
  import IndexViewSettings from '@/modules/elements/index/components/IndexViewSettings.vue';
  import type {SortOption} from '@/modules/elements/types/view-state';
  import type {AdminTableStatusOption} from '../types';
  import AdminTableToolbar from './AdminTableToolbar.vue';
  import CreateActionButton from './CreateActionButton.vue';
  const props = defineProps<{
    searchable: boolean;
    searchPlaceholder?: string | null;
    statusFilterOptions: AdminTableStatusOption[];
    columnsToggleable: boolean;
    viewColumnOptions: CheckboxOption[];
    viewSortOptions: SortOption[];
    createLabel?: string | null;
    createUrl?: string | null;
    createMenuItems?: Array<{label: string; url: string}> | null;
  }>();
  const search = defineModel<string>('search', {required: true});
  const status = defineModel<string>('status', {required: true});
  const viewSortField = defineModel<string>('sortField', {required: true});
  const viewSortDirection = defineModel<'asc' | 'desc'>('sortDirection', {
    required: true,
  });
  const viewTableColumns = defineModel<string[]>('tableColumns', {
    required: true,
  });
  const emit = defineEmits<{'reorder-columns': [options: CheckboxOption[]]}>();
  const hasCreateAction = computed(
    () => !!props.createUrl || !!props.createMenuItems?.length
  );
  function statusOptionFill(option: SelectRichOption): string | undefined {
    return (option as AdminTableStatusOption).fill ?? undefined;
  }
</script>
<template>
  <AdminTableToolbar>
    <template v-if="statusFilterOptions.length" #status>
      <CraftSelectRich
        v-model="status"
        :options="statusFilterOptions"
        :label="t('Status')"
        label-sr-only
      >
        <template #option="{option}">
          <span
            v-if="statusOptionFill(option)"
            class="inline-flex items-center gap-md"
          >
            <craft-indicator :fill="statusOptionFill(option)"></craft-indicator>
            {{ option.label }}
          </span>
          <ElementStatus v-else :label="option.label" value="" />
        </template>
      </CraftSelectRich>
    </template>
    <template v-if="searchable" #search>
      <CraftInput
        name="search"
        :label="t('Search')"
        :placeholder="searchPlaceholder ?? t('Search')"
        label-sr-only
        v-model="search"
      >
        <div slot="suffix" class="flex">
          <craft-button
            v-if="search"
            type="button"
            icon
            size="small"
            variant="plain"
            @click="search = ''"
          >
            <craft-icon name="x" :label="t('Clear search')"></craft-icon>
          </craft-button>
          <span class="flex items-center px-md">
            <craft-icon name="search"></craft-icon>
          </span>
        </div>
      </CraftInput>
    </template>
    <template v-if="columnsToggleable" #view>
      <IndexViewSettings
        :options="viewColumnOptions"
        :sort-options="viewSortOptions"
        v-model:sort-field="viewSortField"
        v-model:sort-direction="viewSortDirection"
        v-model:table-columns="viewTableColumns"
        @reorder="emit('reorder-columns', $event)"
      />
    </template>
    <template v-if="hasCreateAction" #actions>
      <CreateActionButton
        :label="createLabel ?? null"
        :url="createUrl"
        :menu-items="createMenuItems"
      />
    </template>
  </AdminTableToolbar>
</template>
