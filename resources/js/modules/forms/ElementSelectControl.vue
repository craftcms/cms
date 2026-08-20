<script setup lang="ts">
  import '@craftcms/ui/components/chip/chip';
  import {useEventListener} from '@vueuse/core';
  import {computed, reactive, ref, useId} from 'vue';
  import {CraftElementSelectInput} from '@/modules/element-select-input';
  import type {
    FormChangeKind,
    FormControlPayload,
    FormProperties,
  } from './types';
  import {inputName} from './runtime';
  import VarDump from '@/common/components/VarDump.vue';

  /**
   * TODO: Extract the element-select markup into a reusable Vue component
   * equivalent to `_includes/forms/elementSelect.twig`.
   */
  type ElementPresentation = {
    id: number;
    label: string;
    siteId?: number | string | null;
  };
  type ElementInfo = ElementPresentation & {
    $element?: {data?: (key: string) => string | number | null | undefined};
  };
  type ElementSelectElement =
    | 'craft-element-select-input'
    | 'craft-entry-select-input'
    | 'craft-asset-select-input';
  type ElementSelectProps = {
    elementType: string;
    customElement: ElementSelectElement;
    elements: ElementPresentation[];
    sources: string[] | null;
    criteria: FormProperties;
    selectionLabel: string;
    limit: number | null;
    showSiteMenu: boolean;
  };
  const props = defineProps<{
    control: FormControlPayload<ElementSelectProps>;
    value: Array<number | string>;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: number[], kind: FormChangeKind): void;
  }>();
  const elementSelect = ref<CraftElementSelectInput | null>(null);
  const presentations = reactive(new Map<number, ElementPresentation>());
  const id = useId();
  const providedPresentations = computed(
    () =>
      new Map(
        props.control.props.elements.map((element) => [element.id, element])
      )
  );

  const settingsJson = computed(() =>
    JSON.stringify({
      name: inputName(props.control.path),
      elementType: props.control.props.elementType,
      sources: props.control.props.sources,
      criteria: props.control.props.criteria,
      allowAdd: props.editable,
      allowRemove: props.editable,
      editable: props.editable,
      selectable: props.editable,
      limit: props.control.props.limit,
      showSiteMenu: props.control.props.showSiteMenu,
      sortable: false,
      // Explicit so the chips rendered here and the ones the input fetches from
      // `app/render-elements` after a selection agree on having a menu.
      showActionMenu: true,
      modalSettings: {modalTitle: props.control.props.selectionLabel},
    })
  );
  const renderKey = computed(() =>
    JSON.stringify([props.control.props, props.value, props.editable])
  );
  useEventListener(elementSelect, 'removeElements', sync);
  useEventListener(elementSelect, 'selectElements', selected);

  function elementId(value: number | string): number {
    return Number(value);
  }

  function presentation(value: number | string): ElementPresentation {
    const id = elementId(value);

    return (
      providedPresentations.value.get(id) ??
      presentations.get(id) ?? {id, label: String(id)}
    );
  }

  function selected(event: Event): void {
    if (!(event instanceof CustomEvent)) {
      return;
    }

    const elements: ElementInfo[] | undefined = event.detail?.elements;
    elements?.forEach((element) => {
      const selectedId = Number(element.id);
      presentations.set(selectedId, {
        id: selectedId,
        label: String(
          element.label ?? element.$element?.data?.('label') ?? selectedId
        ),
        siteId: element.siteId,
      });
    });
    sync();
  }

  function sync(): void {
    if (!elementSelect.value) {
      return;
    }

    const selected = elementSelect.value.selectedIds.map(Number);
    if (
      selected.length === props.value.length &&
      selected.every((value, index) => value === Number(props.value[index]))
    ) {
      return;
    }

    emit('update:value', selected, 'discrete');
  }
</script>

<template>
  <div>
    <input
      v-if="editable"
      type="hidden"
      :name="inputName(control.path)"
      value=""
    />
    <component
      :is="control.props.customElement"
      ref="elementSelect"
      :key="renderKey"
      :id="id"
      class="elementselect"
      :settings="settingsJson"
    >
      <ul class="elements chips chips-small">
        <li v-for="selectedValue in value" :key="elementId(selectedValue)">
          <craft-chip
            class="element"
            size="small"
            :data-id="String(elementId(selectedValue))"
            :data-site-id="presentation(selectedValue).siteId ?? undefined"
          >
            {{ presentation(selectedValue).label }}
            <input
              v-if="editable"
              type="hidden"
              :name="`${inputName(control.path)}[]`"
              :value="String(elementId(selectedValue))"
            />
            <!--
              Left empty on purpose: `BaseElementSelectInput` calls
              `Craft.addActionsToChip()` on every chip it finds, and this is the
              container it looks for. With one it builds a `craft-action-menu`
              here; without one it falls all the way back to the old jQuery
              disclosure menu and appends it to the chip's body slot. Vue never
              renders children into this div, so the injected menu survives
              re-renders of the surrounding list.
            -->
            <div slot="suffix"></div>
          </craft-chip>
        </li>
      </ul>

      <div v-if="editable" class="flex">
        <button
          type="button"
          class="btn add icon dashed wrap"
          data-element-select-add
          :aria-label="control.props.selectionLabel"
        >
          {{ control.props.selectionLabel }}
        </button>
        <div class="spinner hidden" />
      </div>
    </component>
  </div>
</template>
