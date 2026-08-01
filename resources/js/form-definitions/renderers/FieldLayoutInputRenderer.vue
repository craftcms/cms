<script setup lang="ts">
  import {computed, ref, watch} from 'vue';
  import {t} from '@craftcms/ui/utilities/translate';
  import type {FormElementBinding, JsonValue} from '../types';
  import {
    reorderItems,
    reorderPosition,
    type ReorderDirection,
  } from '../reorder';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/input/input';
  import '@craftcms/ui/components/reorder-button/reorder-button';

  type LayoutElement = Record<string, unknown>;
  type LayoutTab = Record<string, unknown> & {elements?: LayoutElement[]};
  type Layout = Record<string, unknown> & {
    tabs?: LayoutTab[];
    generatedFields?: Array<Record<string, unknown>>;
  };
  type AvailableElement = {
    key: string;
    label: string;
    value: Record<string, JsonValue>;
    multiple: boolean;
  };

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: Layout];
  }>();

  const selection = ref('');
  const layout = computed<Layout>(() =>
    props.binding?.value && typeof props.binding.value === 'object'
      ? (props.binding.value as Layout)
      : {}
  );
  const tabs = computed<LayoutTab[]>(() =>
    Array.isArray(layout.value.tabs) ? layout.value.tabs : []
  );
  const elements = computed<LayoutElement[]>(() =>
    tabs.value.flatMap((tab) =>
      Array.isArray(tab.elements) ? tab.elements : []
    )
  );
  const generatedFields = computed(() =>
    Array.isArray(layout.value.generatedFields)
      ? layout.value.generatedFields
      : []
  );
  const availableElements = computed<AvailableElement[]>(() =>
    Array.isArray(props.config.availableElements)
      ? (props.config.availableElements as AvailableElement[])
      : []
  );
  const selectableElements = computed(() =>
    availableElements.value.filter(
      (option) =>
        option.multiple ||
        !elements.value.some(
          (element) => elementIdentity(element) === optionIdentity(option)
        )
    )
  );

  watch(
    selectableElements,
    (selectableElements) => {
      if (!selectableElements.some(({key}) => key === selection.value)) {
        selection.value = selectableElements[0]?.key ?? '';
      }
    },
    {immediate: true}
  );

  function optionIdentity(option: AvailableElement): string {
    return String(option.value.fieldUid ?? option.value.type ?? option.key);
  }

  function elementIdentity(element: LayoutElement): string {
    return String(element.fieldUid ?? element.type ?? element.uid ?? '');
  }

  function elementLabel(element: LayoutElement): string {
    const authoredLabel = element.label ?? element.heading;

    if (typeof authoredLabel === 'string' && authoredLabel !== '') {
      return authoredLabel;
    }

    return (
      availableElements.value.find(
        (option) => optionIdentity(option) === elementIdentity(element)
      )?.label ?? elementIdentity(element)
    );
  }

  function updateElements(nextElements: LayoutElement[]): void {
    const firstTab = tabs.value[0] ?? {};

    emit('update:value', {
      ...layout.value,
      tabs: [{...firstTab, elements: nextElements}],
    });
  }

  function addElement(): void {
    const option = selectableElements.value.find(
      ({key}) => key === selection.value
    );

    if (!option || props.binding?.readOnly) {
      return;
    }

    updateElements([
      ...elements.value,
      {...option.value, uid: crypto.randomUUID()},
    ]);
  }

  function removeElement(index: number): void {
    updateElements(
      elements.value.filter((_, elementIndex) => elementIndex !== index)
    );
  }

  function reorderElement(
    index: number,
    event: CustomEvent<{direction: ReorderDirection}>
  ): void {
    const reordered = reorderItems(
      elements.value,
      index,
      event.detail.direction
    );

    if (!reordered) {
      return;
    }

    updateElements(reordered);
  }

  function updateGeneratedField(
    index: number,
    property: string,
    event: Event
  ): void {
    const value = (event.target as HTMLInputElement | HTMLTextAreaElement)
      .value;

    emit('update:value', {
      ...layout.value,
      generatedFields: generatedFields.value.map((field, fieldIndex) =>
        fieldIndex === index ? {...field, [property]: value} : field
      ),
    });
  }

  function addGeneratedField(): void {
    emit('update:value', {
      ...layout.value,
      generatedFields: [
        ...generatedFields.value,
        {
          uid: crypto.randomUUID(),
          name: '',
          handle: '',
          template: '',
        },
      ],
    });
  }

  function removeGeneratedField(index: number): void {
    emit('update:value', {
      ...layout.value,
      generatedFields: generatedFields.value.filter(
        (_, fieldIndex) => fieldIndex !== index
      ),
    });
  }

  function reorderGeneratedField(
    index: number,
    event: CustomEvent<{direction: ReorderDirection}>
  ): void {
    const reordered = reorderItems(
      generatedFields.value,
      index,
      event.detail.direction
    );

    if (!reordered) {
      return;
    }

    emit('update:value', {
      ...layout.value,
      generatedFields: reordered,
    });
  }

  function generatedInputName(
    index: number,
    property: string
  ): string | undefined {
    const name = props.attributes.name;

    return typeof name === 'string'
      ? `${name}[generatedFields][${index}][${property}]`
      : undefined;
  }
</script>

<template>
  <div
    :id="typeof attributes.id === 'string' ? attributes.id : undefined"
    role="group"
    :aria-labelledby="
      typeof attributes['aria-labelledby'] === 'string'
        ? attributes['aria-labelledby']
        : undefined
    "
  >
    <div
      v-for="(element, index) in elements"
      :key="String(element.uid ?? `${elementIdentity(element)}:${index}`)"
      data-field-layout-element
      class="field-layout-row"
    >
      <craft-reorder-button
        :disabled="binding?.readOnly || elements.length < 2"
        :position="reorderPosition(index, elements.length)"
        @reorder="reorderElement(index, $event)"
      ></craft-reorder-button>
      <span>{{ elementLabel(element) }}</span>
      <craft-button
        type="button"
        size="small"
        variant="plain"
        :disabled="binding?.readOnly"
        @activate="removeElement(index)"
      >
        {{ t('Remove') }}
      </craft-button>
    </div>

    <div v-if="selectableElements.length" class="field-layout-add">
      <select
        v-model="selection"
        :aria-label="t('Available layout elements')"
        :disabled="binding?.readOnly"
      >
        <option
          v-for="option in selectableElements"
          :key="option.key"
          :value="option.key"
        >
          {{ option.label }}
        </option>
      </select>
      <craft-button
        type="button"
        variant="dashed"
        :disabled="binding?.readOnly"
        @activate="addElement"
      >
        {{ t('Add') }}
      </craft-button>
    </div>

    <div v-if="config.withGeneratedFields === true" class="generated-fields">
      <h3>{{ t('Generated Fields') }}</h3>
      <div
        v-for="(field, index) in generatedFields"
        :key="String(field.uid ?? index)"
        class="generated-field-row"
        data-generated-field
      >
        <craft-reorder-button
          :disabled="binding?.readOnly || generatedFields.length < 2"
          :position="reorderPosition(index, generatedFields.length)"
          @reorder="reorderGeneratedField(index, $event)"
        ></craft-reorder-button>
        <craft-input
          :name="generatedInputName(index, 'name')"
          :value="String(field.name ?? '')"
          :placeholder="t('Name')"
          :aria-label="t('Name')"
          :disabled="binding?.readOnly"
          @input="updateGeneratedField(index, 'name', $event)"
        ></craft-input>
        <craft-input
          :name="generatedInputName(index, 'handle')"
          :value="String(field.handle ?? '')"
          :placeholder="t('Handle')"
          :aria-label="t('Handle')"
          class="code"
          :disabled="binding?.readOnly"
          @input="updateGeneratedField(index, 'handle', $event)"
        ></craft-input>
        <textarea
          :name="generatedInputName(index, 'template')"
          :value="String(field.template ?? '')"
          :placeholder="t('Template')"
          :aria-label="t('Template')"
          class="text fullwidth code"
          rows="2"
          :disabled="binding?.readOnly"
          @input="updateGeneratedField(index, 'template', $event)"
        ></textarea>
        <craft-button
          type="button"
          size="small"
          variant="plain"
          :disabled="binding?.readOnly"
          @activate="removeGeneratedField(index)"
        >
          {{ t('Remove') }}
        </craft-button>
      </div>
      <craft-button
        type="button"
        variant="dashed"
        :disabled="binding?.readOnly"
        @activate="addGeneratedField"
      >
        {{ t('Add a field') }}
      </craft-button>
    </div>
  </div>
</template>

<style scoped>
  .field-layout-row {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .field-layout-add {
    display: flex;
    gap: var(--c-spacing-sm);
    margin-block-start: var(--c-spacing-sm);
  }

  .generated-fields {
    margin-block-start: var(--c-spacing-lg);
  }

  .generated-field-row {
    display: grid;
    grid-template-columns: auto 1fr 1fr 2fr auto;
    gap: var(--c-spacing-sm);
    margin-block-end: var(--c-spacing-sm);
  }
</style>
