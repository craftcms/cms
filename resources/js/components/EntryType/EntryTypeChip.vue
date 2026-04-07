<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import ActionMenu from '@/components/ActionMenu.vue';
  import ReorderButton from '@/components/ReorderButton.vue';
  import type {EntryType} from '@/types';
  import {reactive} from 'vue';
  import VarDump from '@/components/VarDump.vue';
  import Tooltip from '@/components/Tooltip/Tooltip.vue';

  interface EntryTypeOverrides {
    name: string | null;
    handle: string | null;
    description: string | null;
  }

  defineProps<EntryType>();

  const overrides = reactive<EntryTypeOverrides>({
    name: null,
    handle: null,
    description: null,
  });
</script>

<template>
  <craft-chip
    :icon="icon"
    :data-color="color?.value ?? color ?? 'white'"
    :data-id="id"
  >
    <div class="grid gap-1 justify-items-start">
      <div class="flex gap-1">
        <div class="font-bold">
          {{ name }}
        </div>
        <template v-if="description">
          <Tooltip>{{ description }}</Tooltip>
        </template>
      </div>
      <code class="cp-code">{{ handle }}</code>

      <div v-if="indicators">
        <craft-icon
          v-for="indicator in indicators"
          :name="indicator.icon"
          :label="indicator.label"
          :style="{
            color: indicator.iconColor,
          }"
        />
      </div>
    </div>

    <div slot="suffix" class="flex gap-1 items-center">
      <ActionMenu v-if="actions" :actions="actions" />
      <ReorderButton variant="inherit"></ReorderButton>
    </div>
  </craft-chip>
</template>

<style scoped lang="scss"></style>
