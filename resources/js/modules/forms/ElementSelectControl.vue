<script setup lang="ts">
  import '@craftcms/ui/components/chip/chip';
  import {t} from '@craftcms/ui';
  import {useEventListener} from '@vueuse/core';
  import {computed, reactive, ref, useId} from 'vue';
  import {CraftElementSelectInput} from '@/modules/element-select-input';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import type {FormChangeKind, FormControlPayload} from './types';
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
    $element?: {data?: (key: string) => unknown};
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
    criteria: Record<string, unknown>;
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
      showActionMenu: false,
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

  /**
   * The chip's action menu items — the Vue equivalent of the Replace/Remove
   * actions `BaseElementSelectInput::defineElementActions()` injects into the
   * Twig stack's chips, under the same conditions (`allowRemove`, which is
   * `editable` here, plus an element type for Replace). Move forward/backward
   * are omitted because this control isn't sortable.
   *
   * Both hand off to `<craft-element-select-input>`, so the modal flow and the
   * multi-select-aware removal stay in the input where they already live.
   */
  function chipActions(value: number | string): ActionItem[] {
    if (!props.editable) {
      return [];
    }

    const id = elementId(value);
    const actions: ActionItem[] = [];

    if (props.control.props.elementType) {
      actions.push({
        icon: 'arrows-rotate',
        label: t('Replace'),
        onClick: () => elementSelect.value?.replaceElement(id),
      });
    }

    actions.push({
      icon: 'remove',
      label: t('Remove'),
      onClick: () => elementSelect.value?.removeElement(id),
    });

    return actions;
  }

  function selected(event: Event): void {
    const elements = (event as CustomEvent<{elements?: ElementInfo[]}>)?.detail
      ?.elements;
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
            <div slot="suffix">
              <ActionMenu
                v-if="editable"
                :actions="chipActions(selectedValue)"
              />
            </div>
          </craft-chip>
        </li>
      </ul>

      <div v-if="editable" class="flex">
        <craft-button
          type="button"
          variant="dashed"
          icon="plus"
          command="--add-element"
          data-element-select-add
          :aria-label="control.props.selectionLabel"
          @click=""
        >
          {{ control.props.selectionLabel }}
        </craft-button>
        <div class="spinner hidden" />
      </div>
    </component>
  </div>
</template>
