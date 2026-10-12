<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed, useTemplateRef} from 'vue';
  import TypePicker, {
    type TypePickerOption,
  } from '@/common/components/TypePicker.vue';
  import {formatReleaseDate} from '@/modules/releases/types';
  import type {ReleaseOption} from '@/modules/elements/composables/useElementEditor';

  const createValue = '__createRelease__';
  const props = defineProps<{
    releases: ReleaseOption[];
    canCreate: boolean;
    disabled?: boolean;
    buttonVariant?: ButtonVariant;
  }>();
  const emit = defineEmits<{
    select: [release: ReleaseOption];
    create: [opener: HTMLElement | null];
  }>();
  const container = useTemplateRef<HTMLElement>('container');

  const options = computed<TypePickerOption[]>(() => [
    ...props.releases.map((release) => ({
      value: String(release.id),
      label: release.name,
      hint: release.plannedAt
        ? formatReleaseDate(release.plannedAt)
        : t('No planned date'),
      showHint: true,
    })),
    ...(props.canCreate
      ? [
          {
            value: createValue,
            label: t('Create a release'),
          },
        ]
      : []),
  ]);

  function select(value: string): void {
    if (value === createValue) {
      emit(
        'create',
        container.value?.querySelector<HTMLElement>('craft-button') ?? null
      );
      return;
    }

    const release = props.releases.find(
      (release) => String(release.id) === value
    );
    if (release) {
      emit('select', release);
    }
  }
</script>

<template>
  <span ref="container" class="contents">
    <TypePicker
      :types="options"
      :label="t('Add to release')"
      :disabled="disabled"
      :button-variant="buttonVariant ?? ButtonVariant.Solid"
      @select="select"
    />
  </span>
</template>
