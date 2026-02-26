<script setup lang="ts">
  import type {EntryType} from '@/types';
  import {computed} from 'vue';
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import ReorderButton from '@/components/ReorderButton.vue';
  import ActionMenu from '@/components/ActionMenu.vue';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Array<number>): void;
  }>();
  const props = defineProps<{
    modelValue: Array<number>;
    types?: Array<EntryType>;
    actions?: Array<any>;
  }>();

  const selectedTypes = computed(() => {
    return props.modelValue
      .map((id) => {
        return props.types?.find((type) => type.id === id);
      })
      .filter(Boolean);
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
</script>

<template>
  <div>
    <craft-chip v-for="type in selectedTypes">
      <div :data-id="type?.id">
        <div class="font-bold">{{ type?.name }}</div>
        <code>{{ type?.handle }}</code>
      </div>

      <div slot="suffix" class="flex gap-1 items-center">
        <ActionMenu
          :actions="[
            {
              label: t('Settings'),
              icon: 'gear',
            },
            {
              label: t('Remove'),
              variant: 'danger',
              icon: 'x',
              onClick: () => removeItem(type.id),
            },
          ]"
        />
        <ReorderButton></ReorderButton>
      </div>
    </craft-chip>
  </div>

  <div class="flex gap-2 mt-3">
    <craft-action-menu v-if="types?.length">
      <craft-button type="button" slot="invoker">
        <craft-icon name="chevron-down" slot="prefix"></craft-icon>
        {{ t('Choose') }}
      </craft-button>

      <div slot="content">
        <craft-action-item
          v-for="type in types"
          :key="type.id"
          @click="handleTypeSelect(type)"
        >
          <craft-icon
            slot="prefix"
            :name="modelValue.includes(type.id) ? 'check' : ''"
          ></craft-icon>
          <div>
            {{ type.name }}
            <pre>{{ type.handle }}</pre>
          </div>
        </craft-action-item>
      </div>
    </craft-action-menu>
    <craft-button type="button">
      <craft-icon name="plus" slot="prefix"></craft-icon>
      {{ t('Create') }}
    </craft-button>
  </div>
</template>

<style scoped lang="scss">
  craft-chip::part(chip) {
    min-width: 200px;
  }
</style>
