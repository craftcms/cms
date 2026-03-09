<script setup lang="ts">
  import type {EntryType} from '@/types';
  import {computed, ref} from 'vue';
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {type ActionItem} from '@/components/ActionMenu.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import Text from '@/components/Text.vue';
  import {create} from '@actions/Settings/EntryTypesController';
  import EntryTypeChip from '@/components/Entry/EntryTypeChip.vue';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Array<number>): void;
  }>();
  const props = defineProps<{
    modelValue: Array<number>;
    types: Array<EntryType>;
    actions?: Array<ActionItem>;
  }>();

  const settingsSlideoutOpen = ref(false);

  const selectedTypes = computed(() => {
    return props.modelValue
      .map((id) => {
        return props.types?.find((type) => type.id === id) ?? null;
      })
      .filter(Boolean);
  });

  const entryTypeQuery = ref('');

  const selectableTypes = computed(() => {
    return props.types?.filter(
      (type) =>
        type.name.includes(entryTypeQuery.value) ||
        type.handle.includes(entryTypeQuery.value)
    );
  });

  function handleTypeSelect(type: EntryType) {
    let newValue = [...props.modelValue];
    if (newValue.includes(type.id)) {
      newValue.splice(newValue.indexOf(type.id), 1);
    } else {
      newValue.push(type.id);
    }

    emit('update:modelValue', newValue);
  }

  function removeItem(itemId: number) {
    let newValue = [...props.modelValue];
    if (newValue.includes(itemId)) {
      newValue.splice(newValue.indexOf(itemId), 1);
    }
    emit('update:modelValue', newValue);
  }

  function openSlideout() {
    settingsSlideoutOpen.value = true;
  }
</script>

<template>
  <div>
    <template v-for="type in selectedTypes" :key="type?.id">
      <EntryTypeChip v-if="type" :type="type" @click:remove="removeItem" />
    </template>
  </div>

  <div class="flex gap-2 mt-3 items-center">
    <craft-action-menu v-if="types?.length">
      <craft-button type="button" slot="invoker" appearance="filled">
        <craft-icon name="chevron-down" slot="prefix"></craft-icon>
        {{ t('Choose') }}
      </craft-button>

      <div slot="content">
        <div class="p-2">
          <CraftInput
            :label="t('Search')"
            v-model="entryTypeQuery"
            label-sr-only
          >
            <craft-icon name="search" slot="prefix"></craft-icon>
          </CraftInput>
        </div>
        <hr class="m-0" />
        <template v-if="selectableTypes.length < 1">
          <div class="p-2">
            <Text
              template="No entry types match “{query}”"
              :params="{query: entryTypeQuery}"
            />
          </div>
        </template>
        <template v-else>
          <template v-for="type in selectableTypes" :key="type.id">
            <craft-action-item
              @click="handleTypeSelect(type)"
              type="checkbox"
              :icon="type.icon ?? 'empty'"
              :checked="modelValue.includes(type.id)"
              :data-color="type.color?.value ?? 'white'"
            >
              <div>
                {{ type.name }}
                <pre>{{ type.handle }}</pre>
              </div>
            </craft-action-item>
          </template>
        </template>
      </div>
    </craft-action-menu>
    <a :href="create['/admin/settings/entry-types/new']().url" class="">
      <craft-icon name="plus" slot="prefix"></craft-icon>
      {{ t('Create') }}
    </a>
  </div>
</template>

<style scoped lang="scss"></style>
