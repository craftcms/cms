<template>
  <!-- eslint-disable vue/no-v-html -->
  <div class="condition-builder-rule tw-my-1 tw-flex" :class="['depth-' + depth.toString()]">
    <div class="condition-builder-rule-input tw-flex-grow tw-py-2">

      <div class="flex">
        <!--   The rule selector     -->
        <div class="tw-py-2 tw-px-2 tw-bg-gray-200" :class="getRuleInputDepthBorderClass(depth)">
          {{ rule.label }}
        </div>

        <!--   Operands     -->
        <template v-if="typeof rule.operands !== 'undefined'">
          <!-- List of operands (optional) -->
          <div class="select">
            <select
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
        </template>

        <!--   Operator     -->
        <template v-if="typeof rule.operators !== 'undefined' && rule.operators.length > 1">
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
        </template>

        <!-- The input based on the inputType or the custom component -->
        <div>

          <!-- Basic text input -->
          <template v-if="rule.inputType === 'text'">
            <div class="">
              <input
                      v-model="query.value"
                      class="text fullwidth"
                      type="text"
                      :placeholder="labels.textInputPlaceholder"
              >
            </div>
          </template>

          <!-- Basic number input -->
          <template v-if="rule.inputType === 'number'">
            <input
                    v-model="query.value"
                    class=""
                    type="number"
            >
          </template>

          <template v-if="rule.inputType === 'date'">
            <!-- Datepicker -->
            <input
                    v-if="rule.inputType === 'date'"
                    v-model="query.value"
                    class=""
                    type="date"
            >
          </template>

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
          <template v-if="rule.inputType === 'radio'">
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
          <template class="select" v-if="rule.inputType === 'select' && !hasOptionGroups">
            <select
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
          </template>

          <!-- Select with groups -->
          <template v-if="rule.inputType === 'select' && hasOptionGroups">
            <select

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
          </template>

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
        </div>

      </div>

    </div>

    <div class="condition-builder-rule-delete">
      <a @click="remove" v-bind:title="labels.removeRule" role="button" href="#" class="delete icon tw-p-3"></a>
    </div>
  </div>
</template>

<script>
    import ConditionBuilderRule from '../../components/ConditionBuilderRule';
    import Mixins from '../../mixins/mixins.js';

    export default {
        extends: ConditionBuilderRule,
        mixins: [Mixins],
    }
</script>

<style lang="scss">

</style>
