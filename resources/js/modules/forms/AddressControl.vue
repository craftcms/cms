<script setup lang="ts">
  import CraftInput from '@craftcms/ui/components/input/input';
  import CraftSelect from '@craftcms/ui/components/select/select';
  import {actionClient, t} from '@craftcms/ui';
  import '@craftcms/ui/components/field/field';
  import '@craftcms/ui/components/field-group/field-group';
  import {ref, watch} from 'vue';
  import {useFlashMessages} from '@/common/composables/useFlashMessages';
  import type {FormChangeKind, FormControlPayload} from './types';
  import {inputName} from './runtime';

  type AddressFieldName =
    | 'addressLine1'
    | 'addressLine2'
    | 'addressLine3'
    | 'administrativeArea'
    | 'locality'
    | 'dependentLocality'
    | 'postalCode'
    | 'sortingCode';
  type AddressValue = Partial<Record<AddressFieldName, string | null>>;
  type AddressFieldDefinition = {
    name: AddressFieldName;
    label: string;
    type: 'select' | 'text';
    visible: boolean;
    required: boolean;
    autocomplete?: string;
    status?: [string, string] | null;
    options?: Record<string, string>;
    spinner?: boolean;
    width?: number;
  };
  type AddressControlProps = {
    countryCode: string;
    belongsToCurrentUser: boolean;
    fields: AddressFieldDefinition[];
  };

  const props = defineProps<{
    control: FormControlPayload<AddressControlProps>;
    value: AddressValue;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: AddressValue, kind: FormChangeKind): void;
  }>();
  const fields = ref(props.control.props.fields);
  const refreshing = ref<AddressFieldName>();
  const {flash} = useFlashMessages();
  let latestRequest = 0;
  const value = ref(props.value);

  watch(
    () => props.value,
    (current) => (value.value = current)
  );

  watch(
    () => props.control.props,
    (current, previous) => {
      const previousFields = fields.value;
      fields.value = current.fields;

      if (current.countryCode === previous.countryCode) {
        return;
      }

      latestRequest++;
      refreshing.value = undefined;
      const normalized = clearInvalidDownstreamValues(
        value.value,
        'countryCode',
        previousFields,
        current.fields
      );
      if (normalized !== value.value) {
        value.value = normalized;
        emit('update:value', normalized, 'discrete');
      }
    }
  );

  function change(
    field: AddressFieldDefinition,
    event: Event,
    kind: FormChangeKind
  ): void {
    if (event instanceof CustomEvent && event.detail?.initialize) {
      return;
    }

    if (
      !(
        event.target instanceof CraftInput ||
        event.target instanceof CraftSelect
      )
    ) {
      throw new TypeError('Expected an address input event target.');
    }

    const target = event.target;
    const fieldValue = String(target.modelValue ?? '');
    if (value.value[field.name] === fieldValue) {
      return;
    }

    const changed = {...value.value, [field.name]: fieldValue};
    value.value = changed;
    emit('update:value', changed, kind);

    if (field.spinner && fieldValue !== '') {
      void refreshFields(field.name, changed);
    }
  }

  async function refreshFields(
    changedField: AddressFieldName,
    changedValue: AddressValue
  ): Promise<void> {
    const request = ++latestRequest;
    refreshing.value = changedField;

    try {
      const response = await actionClient.post<{
        fieldDefinitions: AddressFieldDefinition[];
      }>('addresses/fields', refreshParams(changedField, changedValue));
      if (request !== latestRequest) {
        return;
      }

      const currentValue = value.value;
      const previousFields = fields.value;
      fields.value = response.data.fieldDefinitions;
      const normalized = clearInvalidDownstreamValues(
        currentValue,
        changedField,
        previousFields,
        fields.value
      );
      if (normalized !== currentValue) {
        value.value = normalized;
        emit('update:value', normalized, 'discrete');
      }
    } catch (error) {
      flash('error', t('A server error occurred.'));
      throw error;
    } finally {
      if (request === latestRequest) {
        refreshing.value = undefined;
      }
    }
  }

  function refreshParams(
    changedField: AddressFieldName,
    changedValue: AddressValue
  ) {
    return {
      namespace: inputName(props.control.path),
      countryCode: props.control.props.countryCode,
      administrativeArea: ['administrativeArea', 'locality'].includes(
        changedField
      )
        ? changedValue.administrativeArea
        : undefined,
      locality: changedField === 'locality' ? changedValue.locality : undefined,
    };
  }

  function clearInvalidDownstreamValues(
    current: AddressValue,
    changedField: AddressFieldName | 'countryCode',
    previousFields: AddressFieldDefinition[],
    nextFields: AddressFieldDefinition[]
  ): AddressValue {
    let normalized = current;
    const hotFields: Array<AddressFieldName | 'countryCode'> = [
      'countryCode',
      'administrativeArea',
      'locality',
    ];
    const changedIndex = hotFields.indexOf(changedField);

    for (const name of hotFields.slice(changedIndex + 1)) {
      if (name === 'countryCode') {
        continue;
      }

      const previous = previousFields.find((field) => field.name === name);
      const next = nextFields.find((field) => field.name === name);
      if (
        (previous?.type !== 'text' || next?.type !== 'text') &&
        normalized[name] !== null
      ) {
        normalized = {...normalized, [name]: null};
      }
    }

    return normalized;
  }
</script>

<template>
  <craft-field-group class="address-fields">
    <craft-field
      v-for="field in fields"
      :key="field.name"
      :class="[
        field.width ? `width-${field.width}` : undefined,
        field.visible ? undefined : 'hidden',
      ]"
      :label="field.label"
      :required="editable && field.required"
      :readonly="control.mode === 'readOnly'"
      :disabled="control.mode === 'disabled'"
      :status="field.status?.[0]"
      :status-label="field.status?.[1]"
    >
      <div slot="input" :class="field.spinner ? 'flex flex-nowrap' : undefined">
        <craft-select
          v-if="field.type === 'select'"
          :name="editable ? inputName([...control.path, field.name]) : ''"
          .modelValue="value[field.name] ?? ''"
          :required="editable && field.required"
          :disabled="!editable"
          @model-value-changed="change(field, $event, 'discrete')"
        >
          <select slot="input" :autocomplete="field.autocomplete">
            <option
              v-for="(label, optionValue) in field.options"
              :key="optionValue"
              :value="optionValue"
            >
              {{ label }}
            </option>
          </select>
        </craft-select>
        <craft-input
          v-else
          :name="editable ? inputName([...control.path, field.name]) : ''"
          type="text"
          .modelValue="value[field.name] ?? ''"
          :autocomplete="field.autocomplete"
          :required="editable && field.required"
          :readonly="control.mode === 'readOnly'"
          :disabled="control.mode === 'disabled'"
          @model-value-changed="change(field, $event, 'typing')"
        />
        <div
          v-if="field.spinner"
          class="spinner"
          :class="{hidden: refreshing !== field.name}"
        />
      </div>
    </craft-field>
  </craft-field-group>
</template>
