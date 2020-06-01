<template>
  <!-- eslint-disable vue/no-v-html -->
  <div class="conditionbuilder-rule">
    <div class="input">
      <label class="">{{ rule.label }}</label>

      <!-- List of operands (optional) -->
      <div class="select">
      <select
        v-if="typeof rule.operands !== 'undefined'"
        v-model="query.operand"
        class=""
      >
        <option
          v-for="operand in rule.operands"
          :key="operand"
        >
          {{ operand }}
        </option>
      </select>
      </div>

      <!-- List of operators (e.g. =, !=, >, <) -->
      <div class="select">
      <select
        v-if="typeof rule.operators !== 'undefined' && rule.operators.length > 1"
        v-model="query.operator"
        class=""
      >
        <option
          v-for="operator in rule.operators"
          :key="operator"
          :value="operator"
        >
          {{ operator }}
        </option>
      </select>
      </div>

      <!-- Basic text input -->
      <input
        v-if="rule.inputType === 'text'"
        v-model="query.value"
        class=""
        type="text"
        :placeholder="labels.textInputPlaceholder"
      >

      <!-- Basic number input -->
      <input
        v-if="rule.inputType === 'number'"
        v-model="query.value"
        class=""
        type="number"
      >

      <!-- Datepicker -->
      <input
        v-if="rule.inputType === 'date'"
        v-model="query.value"
        class=""
        type="date"
      >

      <!-- Custom component input -->
      <div
        v-if="isCustomComponent"
        class="conditionbuilder-custom-component-wrap"
      >
        <component
          :is="rule.component"
          :value="query.value"
          @input="updateQuery"
        />
      </div>

      <!-- Checkbox input -->
      <template
        v-if="rule.inputType === 'checkbox'"
      >
        <div
          v-for="choice in rule.choices"
          :key="choice.value"
          class=""
        >
          <input
            :id="'depth' + depth + '-' + rule.id + '-' + index + '-' + choice.value"
            v-model="query.value"
            type="checkbox"
            :value="choice.value"
            class=""
          >
          <label
            class="form-check-label"
            :for="'depth' + depth + '-' + rule.id + '-' + index + '-' + choice.value"
          >
            {{ choice.label }}
          </label>
        </div>
      </template>

      <!-- Radio input -->
      <template
        v-if="rule.inputType === 'radio'"
      >
        <div
          v-for="choice in rule.choices"
          :key="choice.value"
          class=""
        >
          <input
            :id="'depth' + depth + '-' + rule.id + '-' + index + '-' + choice.value"
            v-model="query.value"
            :name="'depth' + depth + '-' + rule.id + '-' + index"
            type="radio"
            :value="choice.value"
            class=""
          >
          <label
            class=""
            :for="'depth' + depth + '-' + rule.id + '-' + index + '-' + choice.value"
          >
            {{ choice.label }}
          </label>
        </div>
      </template>

      <!-- Select without groups -->
      <select
        v-if="rule.inputType === 'select' && !hasOptionGroups"
        v-model="query.value"
        class=""
        :multiple="rule.type === 'multi-select'"
      >
        <option
          v-for="option in selectOptions"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>

      <!-- Select with groups -->
      <select
        v-if="rule.inputType === 'select' && hasOptionGroups"
        v-model="query.value"
        class=""
        :multiple="rule.type === 'multi-select'"
      >
        <optgroup
          v-for="(option, option_key) in selectOptions"
          :key="option_key"
          :label="option_key"
        >
          <option
            v-for="sub_option in option"
            :key="sub_option.value"
            :value="sub_option.value"
          >
            {{ sub_option.label }}
          </option>
        </optgroup>
      </select>

      <!-- Remove rule button -->
      <button
        type="button"
        class="btn"
        @click="remove"
        v-html="labels.removeRule"
      >
      </button>
    </div>
  </div>
</template>

<script>
import ConditionBuilderRule from '../../components/ConditionBuilderRule';

export default {
  extends: ConditionBuilderRule
}
</script>
