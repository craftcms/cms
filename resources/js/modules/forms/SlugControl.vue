<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import {generateSlug} from '@/modules/input-generators/slug-generator';
  import {computed} from 'vue';
  import {useFormValueGroup} from './formValueGroup';
  import {valueAt} from './runtime';
  import TextControl from './TextControl.vue';
  import type {
    FormChangeKind,
    FormControlPayload,
    FormPayload,
    TextControlProps,
  } from './types';

  defineOptions({inheritAttrs: false});

  type SlugControlProps = TextControlProps & {
    source?: string[];
    charMap?: Record<string, string>;
    autoGenerate?: boolean;
  };

  const props = defineProps<{
    control: FormControlPayload<SlugControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
    values: FormPayload['values'];
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind?: FormChangeKind): void;
    (event: 'change', change: Event): void;
  }>();
  const valueGroup = useFormValueGroup();
  const sourcePath = computed(() => props.control.props.source);
  const charMap = computed(() => props.control.props.charMap);
  const autoGenerate = computed(() => props.control.props.autoGenerate ?? true);
  const generator = useInputGenerator(sourceValue, (sourceValue) => {
    if (props.editable && autoGenerate.value) {
      emit('update:value', generateSlug(sourceValue, charMap.value), 'typing');
    }
  });

  function sourceValue(): string {
    if (!sourcePath.value) {
      return '';
    }

    const path = [...props.control.path.slice(0, -1), ...sourcePath.value];

    return String(
      valueGroup?.valueAt(path) ?? valueAt(props.values, path) ?? ''
    );
  }

  function onChange(event: Event): void {
    generator.markDirty();
    emit('change', event);
  }

  function regenerate(): void {
    if (!window.confirm(t('Are you sure you want to regenerate the slug?'))) {
      return;
    }

    emit(
      'update:value',
      generateSlug(sourceValue(), charMap.value),
      'discrete'
    );
  }
</script>

<template>
  <TextControl
    v-bind="$attrs"
    :control="control"
    :value="value"
    :label="label"
    :editable="editable"
    :invalid="invalid"
    :required="required"
    autocomplete="off"
    autocorrect="off"
    autocapitalize="none"
    @update:value="emit('update:value', $event, 'typing')"
    @change="onChange"
  >
    <template #suffix>
      <craft-button
        v-if="editable && sourcePath && !autoGenerate"
        slot="suffix"
        type="button"
        size="small"
        variant="plain"
        icon="arrows-rotate"
        :aria-label="t('Regenerate slug')"
        :title="t('Regenerate slug')"
        @click="regenerate"
      />
    </template>
  </TextControl>
</template>
