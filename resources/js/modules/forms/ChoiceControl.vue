<script setup lang="ts">
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/button-group/button-group';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/checkbox-group/checkbox-group';
  import '@craftcms/ui/components/checkbox-indeterminate/checkbox-indeterminate';
  import '@craftcms/ui/components/radio/radio';
  import '@craftcms/ui/components/radio-group/radio-group';
  import '@craftcms/ui/components/select/select';
  import {computed, ref, watch} from 'vue';
  import type {CheckboxOption} from '@/common/types';
  import CheckboxGroup from '@/common/form/CheckboxGroup.vue';
  import type {FormControlPayload, FormValue} from './types';
  import type {Slots} from 'vue';
  import {inputName, serverErrorValidators} from './runtime';

  type ChoiceValue = boolean | number | string;
  type ChoicePresentation = CraftCms.Cms.Form.Enums.ChoicePresentation;
  type ChoiceOption = {
    label: string;
    labelHtml?: string;
    /** Icon name; `craft-button` resolves and renders it. */
    icon?: string;
    value: ChoiceValue;
    disabled?: boolean;
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
    /** Label for the “All” checkbox; absent when there isn't one. */
    allLabel?: string;
    /** What “All” posts in `singleValue` mode. */
    allValue?: string;
    allMode?: 'singleValue' | 'eachValue';
    sortable?: boolean;
  };

  const props = defineProps<{
    control: FormControlPayload<ChoiceControlProps>;
    value: FormValue;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string | string[]): void;
  }>();

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
   * `Form\Controls\Choice::selectOptions()` mirrors this for the HTML fallback.
   */
  const selectOptions = computed<ChoiceOption[]>(() => {
    const options = props.control.props.options;

    if (
      props.control.props.multiple ||
      props.required ||
      options.some((option) => inputValue(option.value) === '')
    ) {
      return options;
    }

    return [{label: '', value: ''}, ...options];
  });

  function inputValue(value: FormValue): string {
    return value === true ? '1' : value === false ? '' : String(value);
  }

  function selected(value: ChoiceValue): boolean {
    const values =
      props.control.props.multiple && Array.isArray(props.value)
        ? props.value
        : [props.value];

    return (values ?? []).map(inputValue).includes(inputValue(value));
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

    if (!props.control.props.multiple) {
      emit('update:value', optionValue);
      return;
    }

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
    return `form-${props.control.path.join('-')}-${index}`;
  }

  /**
   * Renders its children with no element of its own, so the option loop can be
   * written once whether or not a `craft-checkbox-indeterminate` wraps it.
   */
  const Passthrough = (_props: unknown, {slots}: {slots: Slots}) =>
    slots.default?.();

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

  function allChecked(): boolean {
    return allIsSingleValue() && selected(allValue());
  }

  /**
   * “All” owns the value while it's checked, so toggling it swaps the whole
   * selection rather than adding to it. Unchecking falls back to nothing
   * selected, which is what the options themselves then build on.
   */
  function onAllChanged(event: Event): void {
    if (!(event.target instanceof HTMLInputElement)) {
      throw new TypeError('Expected a choice input event target.');
    }

    emit('update:value', event.target.checked ? [allValue()] : []);
  }

  function hasThumbnails(): boolean {
    return props.control.props.options.some((option) => option.thumbnail);
  }

  // Reordering needs a drag affordance `craft-checkbox-group` doesn't have on
  // its own, so it renders through the CP's CheckboxGroup instead.
  const useCheckboxGroup = computed(
    () =>
      props.control.props.presentation === 'checkboxes' &&
      props.control.props.sortable === true
  );

  // Display order is owned by the client: the server sends the selected options
  // first, and a drag only reorders — it never changes what is checked.
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

  function optionHtml(value: string): string | undefined {
    return props.control.props.options.find(
      (option) => inputValue(option.value) === value
    )?.labelHtml;
  }

  const groupOptions = computed<CheckboxOption[]>(() => {
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
              value,
              disabled: !props.editable || option.disabled,
            },
          ]
        : [];
    });
  });

  const groupValue = computed<string[]>(() =>
    Array.isArray(props.value) ? props.value.map(inputValue) : []
  );

  function onGroupValue(values: string[]): void {
    emit(
      'update:value',
      order.value.filter((value) => values.includes(value))
    );
  }

  function onGroupReorder(options: CheckboxOption[]): void {
    order.value = options.map((option) => option.value);

    // The value carries the display order, so a reorder changes it too.
    const selected = Array.isArray(props.value)
      ? props.value.map(inputValue)
      : [];
    emit(
      'update:value',
      order.value.filter((value) => selected.includes(value))
    );
  }
</script>

<template>
  <craft-select
    v-if="control.props.presentation === 'select'"
    :name="
      editable
        ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
        : ''
    "
    :disabled="!editable"
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
      <option
        v-for="option in selectOptions"
        :key="inputValue(option.value)"
        :value="inputValue(option.value)"
        :selected="selected(option.value)"
        :disabled="option.disabled"
      >
        {{ option.label }}
      </option>
    </select>
  </craft-select>
  <craft-button-group
    v-else-if="control.props.presentation === 'buttons'"
    :role="control.props.multiple ? 'group' : 'radiogroup'"
    :aria-invalid="invalid ? 'true' : undefined"
    :aria-required="required ? 'true' : undefined"
  >
    <input
      v-if="editable"
      type="hidden"
      :name="inputName(control.path)"
      :value="control.props.multiple ? '' : inputValue(value)"
    />
    <input
      v-for="option in control.props.multiple
        ? control.props.options.filter((option) => selected(option.value))
        : []"
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
      :active="selected(option.value)"
      :disabled="!editable || option.disabled"
      :icon.attr="option.icon"
      :aria-label="option.icon || option.labelHtml ? option.label : undefined"
      @click="onButtonClicked(option.value)"
    >
      <!-- An icon option slots nothing: `craft-button` renders the icon
           itself, and only stays square while its light DOM is empty. -->
      <span v-if="!option.icon && option.labelHtml" v-html="option.labelHtml" />
      <template v-else-if="!option.icon">{{ option.label }}</template>
    </craft-button>
  </craft-button-group>
  <CheckboxGroup
    v-else-if="useCheckboxGroup"
    :name="editable ? `${inputName(control.path)}[]` : undefined"
    :disabled="!editable"
    :model-value="groupValue"
    :options="groupOptions"
    :sortable="control.props.sortable"
    @update:model-value="onGroupValue"
    @update:options="onGroupReorder"
  >
    <template #label="{option}">
      <span v-if="optionHtml(option.value)" v-html="optionHtml(option.value)" />
      <template v-else>{{ option.label }}</template>
    </template>
  </CheckboxGroup>
  <component
    :is="control.props.multiple ? 'craft-checkbox-group' : 'craft-radio-group'"
    v-else
    :thumbnails="hasThumbnails() || undefined"
    :name="
      editable
        ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
        : ''
    "
    :required="editable && required"
    :disabled="!editable"
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
    <component
      :is="
        control.props.allLabel ? 'craft-checkbox-indeterminate' : Passthrough
      "
      :label="control.props.allLabel"
      :all-mode="allIsSingleValue() ? 'single-value' : 'each-value'"
      .choiceValue="allValue()"
      .checked="allChecked()"
    >
      <input
        v-if="control.props.allLabel && allIsSingleValue() && editable"
        slot="input"
        :id="`${optionId(0)}-all`"
        type="checkbox"
        :name="`${inputName(control.path)}[]`"
        :value="allValue()"
        :checked="allChecked()"
        @change="onAllChanged"
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
          :disabled="!editable || option.disabled"
          .choiceValue="inputValue(option.value)"
          .checked="allChecked() || selected(option.value)"
        >
          <input
            slot="input"
            :id="optionId(index)"
            :type="control.props.multiple ? 'checkbox' : 'radio'"
            :name="
              editable && !allChecked()
                ? `${inputName(control.path)}${control.props.multiple ? '[]' : ''}`
                : ''
            "
            :value="inputValue(option.value)"
            :checked="allChecked() || selected(option.value)"
            :disabled="!editable || option.disabled"
            :required="editable && required && !control.props.multiple"
            :aria-invalid="invalid ? 'true' : undefined"
            @change="onOptionChanged"
          />
          <label slot="label" :for="optionId(index)">
            <span v-if="option.labelHtml" v-html="option.labelHtml" />
            <template v-else>{{ option.label }}</template>
          </label>
        </component>
      </div>
    </component>
  </component>
</template>
