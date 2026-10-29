<script setup lang="ts">
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/button-group/button-group';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/checkbox-group/checkbox-group';
  import '@craftcms/ui/components/checkbox-select/checkbox-select';
  import type CraftCheckboxSelect from '@craftcms/ui/components/checkbox-select/checkbox-select';
  import type {CheckboxSelectOption} from '@craftcms/ui/components/checkbox-select/checkbox-select';
  import '@craftcms/ui/components/icon/icon';
  import '@craftcms/ui/components/indicator/indicator';
  import '@craftcms/ui/components/radio/radio';
  import '@craftcms/ui/components/radio-group/radio-group';
  import '@craftcms/ui/components/select/select';
  import {computed, inject, ref, watch} from 'vue';
  import type {UiControlPayload, UiValue} from './types';
  import {FieldLabelSrOnly, inputName, serverErrorValidators} from './runtime';

  type ChoiceValue = boolean | number | string;
  type ChoicePresentation = CraftCms.Cms.Ui.Enums.ChoicePresentation;
  type ChoiceOption = {
    label: string;
    labelHtml?: string;
    /**
     * Icon name. A button renders it in place of the label; a checkbox or
     * radio shows it before the label, tinted with `color`.
     */
    icon?: string;
    /** Shown before a checkbox or radio label: tints the icon, or as a dot. */
    color?: string;
    value: ChoiceValue;
    disabled?: boolean;
    /**
     * Heads a run of consecutive options sharing it, as an `<optgroup>` when
     * presented as a select. Other presentations ignore it.
     */
    group?: string | null;
    /**
     * Illustration of the choice, rendered above the radio. `aspectRatio` maps
     * to the CSS property of the same name.
     */
    thumbnail?: {
      src: string;
      width?: number;
      height?: number;
      aspectRatio?: string;
    };
  };
  type ChoiceControlProps = {
    options: ChoiceOption[];
    multiple: boolean;
    presentation: ChoicePresentation;
    /** Absent means a button with an icon shows only the icon. */
    iconsOnly?: false;
    /** Label for the “All” checkbox; absent when there isn't one. */
    allLabel?: string;
    /** What “All” posts in `singleValue` mode. */
    allValue?: string;
    allMode?: 'singleValue' | 'eachValue';
    sortable?: boolean;
    /**
     * Label for the leading blank option. Absent leaves it unlabeled; `false`
     * means the control has no valid empty state and shouldn't offer one.
     */
    placeholder?: string | false;
  };

  const props = defineProps<{
    control: UiControlPayload<ChoiceControlProps>;
    value: UiValue;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string | string[]): void;
  }>();
  const fieldLabelSrOnly = inject(FieldLabelSrOnly, undefined);

  /** Whether a button shows only its option's icon, named by the label. */
  function iconOnly(option: ChoiceOption): boolean {
    return option.icon !== undefined && props.control.props.iconsOnly !== false;
  }

  /**
   * A single select needs somewhere to represent "nothing chosen".
   *
   * A `<select>` with no selected option shows its first one, so an optional
   * setting that has never been set looks set — and because nothing changed,
   * saving posts nothing and it stays unset. A leading blank option makes the
   * empty state visible, and re-selectable once something has been chosen.
   *
   * Multi-selects show an empty list when empty, so they don't need it, and a
   * required control has no valid empty state to offer. Options that already
   * carry an empty value supply their own.
   *
   * `Choice::placeholder()` labels it and `Choice::withoutPlaceholder()` drops
   * it for a setting that has no valid empty state.
   *
   * `UI\Controls\Choice::selectOptions()` mirrors this for the HTML fallback.
   */
  const selectOptions = computed<ChoiceOption[]>(() => {
    const options = props.control.props.options;
    const placeholder = props.control.props.placeholder;

    if (
      props.control.props.multiple ||
      props.required ||
      placeholder === false ||
      options.some((option) => inputValue(option.value) === '')
    ) {
      return options;
    }

    return [{label: placeholder ?? '', value: ''}, ...options];
  });

  /**
   * The select's options in runs, one per `group` in the order they appear;
   * an ungrouped run has no label and renders without an `<optgroup>`. A run
   * is keyed by its label and first option rather than its position.
   */
  const selectGroups = computed(() =>
    selectOptions.value.reduce<
      Array<{key: string; label: string | null; options: ChoiceOption[]}>
    >((groups, option) => {
      const label = option.group ?? null;
      const last = groups.at(-1);

      if (last && last.label === label) {
        last.options.push(option);
      } else {
        groups.push({
          key: JSON.stringify([label, inputValue(option.value)]),
          label,
          options: [option],
        });
      }

      return groups;
    }, [])
  );

  function inputValue(value: UiValue): string {
    return value === true ? '1' : value === false ? '' : String(value);
  }

  const selectedValues = computed(() => {
    const values =
      props.control.props.multiple && Array.isArray(props.value)
        ? props.value
        : [props.value];

    return new Set(values.map(inputValue));
  });

  const selectedOptions = computed(() =>
    props.control.props.options.filter((option) => selected(option.value))
  );

  function selected(value: ChoiceValue): boolean {
    return selectedValues.value.has(inputValue(value));
  }

  function onSelect(event: Event): void {
    if (!(event.target instanceof HTMLSelectElement)) {
      throw new TypeError('Expected a select event target.');
    }

    const select = event.target;
    emit(
      'update:value',
      props.control.props.multiple
        ? Array.from(select.selectedOptions, (option) => option.value)
        : select.value
    );
  }

  function onOptionChanged(event: Event): void {
    if (!(event.target instanceof HTMLInputElement)) {
      throw new TypeError('Expected a choice input event target.');
    }

    const input = event.target;

    if (!props.control.props.multiple) {
      emit('update:value', input.value);
      return;
    }

    const values = Array.isArray(props.value)
      ? props.value.map(inputValue)
      : [];
    emit(
      'update:value',
      input.checked
        ? [...values, input.value]
        : values.filter((value) => value !== input.value)
    );
  }

  function onButtonClicked(value: ChoiceValue): void {
    const optionValue = inputValue(value);
    const values = Array.isArray(props.value)
      ? props.value.map(inputValue)
      : [];
    emit(
      'update:value',
      values.includes(optionValue)
        ? values.filter((value) => value !== optionValue)
        : [...values, optionValue]
    );
  }

  function optionId(index: number): string {
    return `ui-${props.control.path.join('-')}-${index}`;
  }

  /**
   * Whether “All” speaks for the options rather than merely toggling them: it
   * posts its own token and they post nothing.
   */
  function allIsSingleValue(): boolean {
    return (
      props.control.props.allLabel !== undefined &&
      props.control.props.allMode !== 'eachValue'
    );
  }

  function allValue(): string {
    return props.control.props.allValue ?? '*';
  }

  function hasThumbnails(): boolean {
    return props.control.props.options.some((option) => option.thumbnail);
  }

  // Sorting, an “All” option, and the “Add item” menu render through
  // `craft-checkbox-select`; plain checkboxes stay a `craft-checkbox-group`.
  const useCheckboxSelect = computed(
    () =>
      props.control.props.presentation === 'add-menu' ||
      (props.control.props.presentation === 'checkboxes' &&
        (props.control.props.sortable === true ||
          props.control.props.allLabel !== undefined))
  );

  // Display order is owned by the client: the server sends the selected
  // options first, and the element reorders its items itself, so read the
  // order back from it rather than putting the old one back on the next render.
  const order = ref<string[]>(optionValues(props.control.props.options));

  watch(
    () => props.control.props.options,
    (options) => {
      const values = optionValues(options);
      order.value = [
        ...order.value.filter((value) => values.includes(value)),
        ...values.filter((value) => !order.value.includes(value)),
      ];
    }
  );

  function optionValues(options: ChoiceOption[]): string[] {
    return options.map((option) => inputValue(option.value));
  }

  const selectOptionsInOrder = computed<CheckboxSelectOption[]>(() => {
    const byValue = new Map(
      props.control.props.options.map((option) => [
        inputValue(option.value),
        option,
      ])
    );

    return order.value.flatMap((value) => {
      const option = byValue.get(value);

      return option
        ? [
            {
              label: option.label,
              ...(option.labelHtml !== undefined
                ? {labelHtml: option.labelHtml}
                : {}),
              value,
              disabled: !!option.disabled,
            },
          ]
        : [];
    });
  });

  const groupValue = computed<string[]>(() =>
    Array.isArray(props.value) ? props.value.map(inputValue) : []
  );

  function onCheckboxSelectValue(event: Event): void {
    // Its checkboxes fire their own `model-value-changed`, which bubbles up
    // to this listener too.
    const select = event.currentTarget;
    if (
      event.target !== select ||
      !(select instanceof HTMLElement) ||
      select.localName !== 'craft-checkbox-select'
    ) {
      return;
    }

    // SAFETY: Checked by tag name above.
    const element = select as CraftCheckboxSelect;
    order.value = element.options.map((option) => option.value);
    emit('update:value', element.modelValue);
  }
</script>

<template>
  <craft-select
    v-if="control.props.presentation === 'select'"
    .labelSrOnly="fieldLabelSrOnly"
    :name="
      editable
        ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
        : ''
    "
    .disabled="!editable"
    :required="editable && required"
    .validators="serverErrorValidators(invalid)"
  >
    <select
      slot="input"
      :name="
        editable
          ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
          : ''
      "
      :multiple="control.props.multiple"
      :disabled="!editable"
      :required="editable && required"
      :aria-invalid="invalid ? 'true' : undefined"
      @change="onSelect"
    >
      <template v-for="group in selectGroups" :key="group.key">
        <optgroup v-if="group.label !== null" :label="group.label">
          <option
            v-for="option in group.options"
            :key="inputValue(option.value)"
            :value="inputValue(option.value)"
            :selected="selected(option.value)"
            :disabled="option.disabled"
          >
            {{ option.label }}
          </option>
        </optgroup>
        <template v-else>
          <option
            v-for="option in group.options"
            :key="inputValue(option.value)"
            :value="inputValue(option.value)"
            :selected="selected(option.value)"
            :disabled="option.disabled"
          >
            {{ option.label }}
          </option>
        </template>
      </template>
    </select>
  </craft-select>
  <craft-button-group
    v-else-if="
      control.props.presentation === 'buttons' && control.props.multiple
    "
    role="group"
    :aria-invalid="invalid ? 'true' : undefined"
    :aria-required="required ? 'true' : undefined"
  >
    <input
      v-if="editable"
      type="hidden"
      :name="inputName(control.path)"
      value=""
    />
    <input
      v-for="option in selectedOptions"
      :key="inputValue(option.value)"
      type="hidden"
      :name="`${inputName(control.path)}[]`"
      :value="inputValue(option.value)"
    />
    <!-- `icon` is bound as an attribute because `craft-button` doesn't reflect
         it, and its square icon-only treatment keys on the attribute. -->
    <craft-button
      v-for="option in control.props.options"
      :key="inputValue(option.value)"
      type="button"
      :value="inputValue(option.value)"
      .active="selected(option.value)"
      .disabled="!editable || option.disabled"
      :icon.attr="option.icon"
      :aria-label="
        iconOnly(option) || option.labelHtml ? option.label : undefined
      "
      @click="onButtonClicked(option.value)"
    >
      <!-- An icon-only option slots nothing: `craft-button` renders the icon
           itself, and only stays square while its light DOM is empty. -->
      <span
        v-if="!iconOnly(option) && option.labelHtml"
        v-html="option.labelHtml"
      />
      <template v-else-if="!iconOnly(option)">{{ option.label }}</template>
    </craft-button>
  </craft-button-group>
  <craft-checkbox-select
    v-else-if="useCheckboxSelect"
    role="group"
    :name="editable ? inputName(control.path) : undefined"
    .disabled="!editable"
    .sortable="control.props.sortable === true"
    :presentation="
      control.props.presentation === 'add-menu' ? 'add-menu' : 'checkboxes'
    "
    :all-label="control.props.allLabel ?? null"
    :all-value="allValue()"
    :all-mode="allIsSingleValue() ? 'single-value' : 'each-value'"
    :aria-invalid="invalid ? 'true' : undefined"
    :aria-required="required ? 'true' : undefined"
    .options="selectOptionsInOrder"
    .modelValue="groupValue"
    @model-value-changed="onCheckboxSelectValue"
  ></craft-checkbox-select>
  <component
    :is="control.props.multiple ? 'craft-checkbox-group' : 'craft-radio-group'"
    v-else
    :thumbnails="hasThumbnails() || undefined"
    :buttons="control.props.presentation === 'buttons' || undefined"
    :name="
      editable
        ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
        : ''
    "
    :required="editable && required"
    .disabled="!editable"
    :aria-invalid="invalid ? 'true' : undefined"
    :aria-required="required ? 'true' : undefined"
    .validators="serverErrorValidators(invalid)"
  >
    <input
      v-if="control.props.multiple && editable"
      type="hidden"
      :name="inputName(control.path)"
      value=""
    />
    <div
      v-for="(option, index) in control.props.options"
      :key="inputValue(option.value)"
    >
      <label
        v-if="option.thumbnail"
        class="radio-thumbnail"
        :for="optionId(index)"
      >
        <img
          :src="option.thumbnail.src"
          :width="option.thumbnail.width"
          :height="option.thumbnail.height"
          :style="
            option.thumbnail.aspectRatio
              ? {aspectRatio: option.thumbnail.aspectRatio}
              : undefined
          "
          alt=""
        />
      </label>
      <component
        :is="control.props.multiple ? 'craft-checkbox' : 'craft-radio'"
        :title="
          control.props.presentation === 'buttons' && iconOnly(option)
            ? option.label
            : undefined
        "
        .disabled="!editable || option.disabled"
        .choiceValue="inputValue(option.value)"
        .checked="selected(option.value)"
      >
        <input
          slot="input"
          :id="optionId(index)"
          :type="control.props.multiple ? 'checkbox' : 'radio'"
          :name="
            editable
              ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
              : ''
          "
          :value="inputValue(option.value)"
          :checked="selected(option.value)"
          :disabled="!editable || option.disabled"
          :required="editable && required && !control.props.multiple"
          :aria-invalid="invalid ? 'true' : undefined"
          @change="onOptionChanged"
        />
        <label slot="label" :for="optionId(index)">
          <craft-icon
            v-if="control.props.presentation === 'buttons' && iconOnly(option)"
            :name="option.icon"
            :label="option.label"
          />
          <template v-else>
            <craft-icon
              v-if="option.icon"
              :name="option.icon"
              :style="option.color ? {color: option.color} : undefined"
            />
            <craft-indicator v-else-if="option.color" :fill="option.color" />
            <span v-if="option.labelHtml" v-html="option.labelHtml" />
            <template v-else>{{ option.label }}</template>
          </template>
        </label>
      </component>
    </div>
  </component>
</template>
