<script setup lang="ts" generic="T">
  import '@craftcms/ui/components/button/button';
  import {t} from '@craftcms/ui';
  import {useElementSize} from '@vueuse/core';
  import {computed, nextTick, ref, useId, watch} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItems} from '@/common/types';

  const props = defineProps<{
    choices: Array<{
      value: T;
      label: string;
      icon?: string | null;
      color?: string | null;
      group?: string | null;
    }>;
    label: string;
    disabled: boolean;
    adding?: T | null;
  }>();
  const emit = defineEmits<{
    create: [value: T, opener: HTMLElement];
  }>();

  const invokerId = useId();
  const addArea = ref<HTMLElement>();
  const addButtons = ref<HTMLElement>();
  const {width: addAreaWidth} = useElementSize(addArea);
  const buttonsWidth = ref(0);
  const groups = computed(() => {
    const groups = new Map<string, typeof props.choices>();

    for (const choice of props.choices) {
      const group = choice.group ?? '';
      groups.set(group, [...(groups.get(group) ?? []), choice]);
    }

    return [...groups];
  });

  /** Remember the row's natural width so switching to the menu cannot change the threshold. */
  const addFromMenu = computed(
    () =>
      groups.value.length > 1 ||
      (buttonsWidth.value > 0 &&
        addAreaWidth.value > 0 &&
        addAreaWidth.value < buttonsWidth.value)
  );

  function menuInvoker() {
    return addArea.value?.querySelector<
      HTMLElement & {loading: boolean; disabled: boolean}
    >('craft-button[slot="invoker"]');
  }

  /** ActionMenu freezes its invoker to keep Vue from patching overlay-managed DOM. */
  watch(
    [() => props.adding, () => props.disabled, addFromMenu],
    () => {
      const invoker = menuInvoker();

      if (invoker) {
        invoker.loading = props.adding != null;
        invoker.disabled = props.disabled;
      }
    },
    {flush: 'post'}
  );

  watch(
    [addButtons, () => props.choices],
    async () => {
      await nextTick();

      if (addButtons.value) {
        buttonsWidth.value = addButtons.value.scrollWidth;
      }
    },
    {immediate: true}
  );

  const actions = computed<ActionItems>(() =>
    groups.value.map(([group, choices]) => ({
      type: 'group',
      ...(group === '' ? {} : {heading: group}),
      items: choices.map((choice) => ({
        label: t('Add {type}', {type: choice.label}),
        icon: choice.icon ?? 'plus',
        iconColor: choice.color ?? undefined,
        disabled: props.disabled,
        onClick: () => emit('create', choice.value, menuInvoker()!),
      })),
    }))
  );
</script>

<template>
  <div ref="addArea" class="min-w-0">
    <ActionMenu
      v-if="addFromMenu"
      :for="invokerId"
      :actions="actions"
      :searchable="choices.length > 5"
      :label="label"
    >
      <template #invoker="{attributes}">
        <craft-button
          v-bind="attributes"
          :id="invokerId"
          data-create-element
          type="button"
          variant="dashed"
          icon="plus"
          .disabled="disabled"
          .loading="adding != null"
        >
          {{ label }}
        </craft-button>
      </template>
    </ActionMenu>
    <div v-else ref="addButtons" class="flex w-max gap-sm items-center">
      <craft-button
        v-for="(choice, index) in choices"
        :key="index"
        data-create-element
        type="button"
        variant="dashed"
        :icon="choice.icon ?? 'plus'"
        :data-color="choice.color ?? undefined"
        .loading="adding === choice.value"
        .disabled="disabled"
        :data-form-matrix-add="
          typeof choice.value === 'string' ? choice.value : undefined
        "
        @click.stop.prevent="
          emit('create', choice.value, $event.currentTarget as HTMLElement)
        "
      >
        {{
          choices.length === 1 ? label : t('Add {type}', {type: choice.label})
        }}
      </craft-button>
    </div>
  </div>
</template>
