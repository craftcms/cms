<script setup lang="ts">
  import {
    getNestedKeys,
    hasNested,
    type PermissionItem,
  } from '@/utils/permissions';
  import CraftCheckbox from '@craftcms/cp/vue/CraftCheckbox.vue';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Array<string>): void;
  }>();
  const props = withDefaults(
    defineProps<{
      modelValue: Array<string>;
      permissions?: Record<string, PermissionItem>;
      disabled?: boolean;
      level?: number;
    }>(),
    {permissions: () => ({}), modelValue: () => [], disabled: false, level: 0}
  );

  function toggleItem(key: string) {
    const lowerKey = key.toLowerCase();
    const index = props.modelValue.indexOf(lowerKey);
    if (index === -1) {
      emit('update:modelValue', [...props.modelValue, lowerKey]);
    } else {
      const keysToRemove = new Set([
        lowerKey,
        ...getNestedKeys(props.permissions[key]),
      ]);
      emit(
        'update:modelValue',
        props.modelValue.filter((v) => !keysToRemove.has(v))
      );
    }
  }
</script>

<template>
  <ul
    class="group"
    v-for="(item, key) in permissions"
    :key="key"
    :style="{
      '--gap-x': `calc((${level} * 1lh) + var(--c-spacing-md))`,
    }"
  >
    <li>
      <CraftCheckbox
        :label="item.label"
        :model-value="modelValue.includes(key.toLowerCase())"
        :value="key"
        :disabled="disabled"
        @update:model-value="toggleItem(key)"
        :class="{
          'cp-checkbox-indentation': level! > 0,
        }"
      >
        <div v-if="item.info || item.warning" slot="help-text">
          <template v-if="item.info">
            {{ item.info }}
          </template>

          <template v-if="item.warning">
            <div class="flex gap-1 items-center" data-color="warning">
              <craft-icon name="triangle-exclamation"></craft-icon>
              {{ item.warning }}
            </div>
          </template>
        </div>
      </CraftCheckbox>

      <PermissionList
        v-if="hasNested(item)"
        :permissions="item.nested"
        :model-value="modelValue"
        :disabled="disabled || !modelValue.includes(item.key.toLowerCase())"
        @update:model-value="emit('update:modelValue', $event)"
        :level="level! + 1"
      />
    </li>
  </ul>
</template>

<style scoped lang="scss">
  .label {
    display: flex;
  }

  .group {
    margin-block: var(--c-spacing-sm);
  }

  .cp-checkbox-indentation {
    position: relative;
  }

  .cp-checkbox-indentation::before {
    content: '';
    position: absolute;
    // Position the indicator halfway from the top of the checkbox
    top: calc(1lh / 2);
    left: calc(var(--c-size-control-2xs) + (var(--c-spacing) * 2));
    width: calc(var(--gap-x) - (var(--c-spacing) * 3.5));
    height: 1px;
    background-color: var(--c-color-neutral-border-quiet);
  }
</style>
