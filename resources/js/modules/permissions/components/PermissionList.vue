<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {
    getNestedKeys,
    hasNested,
    type PermissionItem,
  } from '@/modules/permissions/helpers/permissions';
  import CraftCheckbox from '@craftcms/cp/vue/CraftCheckbox.vue';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Array<string>): void;
  }>();
  const props = withDefaults(
    defineProps<{
      modelValue: Array<string>;
      permissions?: Record<string, PermissionItem>;
      heading?: string;
      permissionKeys?: Array<string>;
      lockedPermissions?: Array<string>;
      disabled?: boolean;
      level?: number;
    }>(),
    {
      permissions: () => ({}),
      modelValue: () => [],
      permissionKeys: () => [],
      lockedPermissions: () => [],
      disabled: false,
      level: 0,
    }
  );

  function lockedSet() {
    return new Set(props.lockedPermissions);
  }

  function selectableKeys() {
    const locked = lockedSet();

    return props.permissionKeys.filter((key) => !locked.has(key));
  }

  function isSelected(key: string) {
    return props.modelValue.includes(key) || lockedSet().has(key);
  }

  function isDisabled(key: string) {
    return props.disabled || lockedSet().has(key);
  }

  function allSelected() {
    const keys = selectableKeys();

    if (!keys.length) {
      return false;
    }

    const selected = new Set(props.modelValue);

    return keys.every((key) => selected.has(key));
  }

  function toggleAll() {
    const keys = selectableKeys();

    if (allSelected()) {
      const keysToRemove = new Set(keys);
      emit(
        'update:modelValue',
        props.modelValue.filter((key) => !keysToRemove.has(key))
      );
      return;
    }

    emit('update:modelValue', [...new Set([...props.modelValue, ...keys])]);
  }

  function setItemSelected(key: string, selected: boolean) {
    if (isDisabled(key)) {
      return;
    }

    const index = props.modelValue.indexOf(key);
    if (selected && index === -1) {
      emit('update:modelValue', [...props.modelValue, key]);
      return;
    }

    if (selected || index === -1) {
      return;
    }

    const keysToRemove = new Set([
      key,
      ...getNestedKeys(props.permissions[key]),
    ]);
    emit(
      'update:modelValue',
      props.modelValue.filter((value) => !keysToRemove.has(value))
    );
  }
</script>

<template>
  <div v-if="heading" class="flex gap-2 items-center">
    <h3 class="m-0! text-base">
      {{ heading }}
    </h3>

    <craft-button
      type="button"
      size="small"
      appearance="plain"
      :disabled="disabled"
      @click="toggleAll"
    >
      <template v-if="allSelected()">
        {{ t('Deselect all') }}
      </template>
      <template v-else>
        {{ t('Select all') }}
      </template>
    </craft-button>
  </div>

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
        :model-value="isSelected(key)"
        :value="key"
        :disabled="isDisabled(key)"
        @update:model-value="setItemSelected(key, $event)"
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
        :locked-permissions="lockedPermissions"
        :disabled="disabled || !isSelected(item.key)"
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
    inset-block-start: calc(1lh / 2);
    inset-inline-start: calc(
      var(--c-size-control-2xs) + (var(--c-spacing) * 2)
    );
    width: calc(var(--gap-x) - (var(--c-spacing) * 3.5));
    height: 1px;
    background-color: var(--c-color-neutral-border-quiet);
  }
</style>
