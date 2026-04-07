<script setup lang="ts">
  import ActionMenu from '@/components/ActionMenu.vue';
  import ReorderButton from '@/components/ReorderButton.vue';
  import type {EntryType} from '@/types';
  import Tooltip from '@/components/Tooltip/Tooltip.vue';

  defineProps<
    Pick<
      EntryType,
      | 'name'
      | 'id'
      | 'handle'
      | 'color'
      | 'icon'
      | 'description'
      | 'indicators'
      | 'actions'
    >
  >();
</script>

<template>
  <craft-chip :data-color="color?.value ?? color ?? 'white'" :data-id="id">
    <template v-if="icon">
      <craft-icon slot="icon" v-bind="icon" />
    </template>
    <div class="grid gap-1 justify-items-start">
      <div class="flex gap-1">
        <div class="font-bold">
          {{ name }}
        </div>
        <template v-if="description">
          <Tooltip>{{ description }}</Tooltip>
        </template>
      </div>
      <div class="cp-code">{{ handle }}</div>

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

<style scoped lang="scss">
  // Some special styles for nice icon alignment. We might want to move this
  // into chips, but for right now this is the only spot
  craft-chip::part(prefix) {
    align-self: start;
    height: 1lh;
    justify-content: center;
  }
</style>
