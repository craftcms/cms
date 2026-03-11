<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import ReorderButton from '@/components/ReorderButton.vue';
  import ActionMenu from '@/components/ActionMenu.vue';
  import type {EntryType} from '@/types';
  import Slideout from '@/components/Slideout/Slideout.vue';
  import {computed, ref} from 'vue';
  import {useFetch} from '@/composables/useFetch';
  import {renderOverrideSettings} from '@actions/Settings/EntryTypesController';

  const emit = defineEmits<{
    (e: 'click:remove', typeId: number): void;
  }>();
  const props = defineProps<{
    type: EntryType;
  }>();

  const settingsSlideoutOpen = ref(false);
  const {execute, data, isSuccess} = useFetch(renderOverrideSettings().url, {
    method: 'POST',
    immediate: false,
  });

  const keyColor = computed(() => {
    if (typeof props.type.color === 'string') {
      return props.type.color;
    }

    return props.type.color?.value ?? 'white';
  });

  async function openSettings() {
    await execute({
      id: props.type.id,
    });
    settingsSlideoutOpen.value = true;
  }
</script>

<template>
  <craft-chip :icon="type.icon" :data-color="keyColor">
    <div :data-id="type.id">
      <div class="font-bold">{{ type.name }}</div>
      <code>{{ type.handle }}</code>
    </div>

    <div slot="suffix" class="flex gap-1 items-center">
      <ActionMenu
        :actions="[
          {
            label: t('Settings'),
            icon: 'gear',
            onClick: () => openSettings(),
          },
          {
            label: t('Remove'),
            variant: 'danger',
            icon: 'x',
            onClick: () => emit('click:remove', type.id),
          },
        ]"
      />
      <ReorderButton variant="inherit"></ReorderButton>
    </div>
  </craft-chip>

  <Slideout
    :active="settingsSlideoutOpen"
    @close="settingsSlideoutOpen = false"
    :title="t('Edit {name}', {name: type.name})"
  >
    <template v-if="isSuccess">
      <div v-html="data.settingsHtml"></div>
    </template>

    <template #secondary-action>
      <craft-button @click="settingsSlideoutOpen = false">Close</craft-button>
    </template>
    <template #primary-action>
      <craft-button type="submit">Apply</craft-button>
    </template>
  </Slideout>
</template>

<style scoped lang="scss">
  craft-chip::part(chip) {
    min-width: 200px;
  }

  // Some special styles for nice icon alignment. We might want to move this
  // into chips, but for right now this is the only spot
  craft-chip::part(prefix) {
    align-self: start;
    height: 1lh;
    justify-content: center;
  }
</style>
