<template>
  <!-- eslint-disable vue/no-v-html -->
  <div
          class="conditionbuilder-group"
          :class="'depth-' + depth.toString()"
  >
    <div class="conditionbuilder-group-heading">
      <div class="match-type-container">
        <label
                class=""
                for="conditionbuilder-match-type"
        >
          {{ labels.matchType }}
        </label>

      <div class="select">
        <select
                id="conditionbuilder-match-type"
                v-model="query.logicalOperator"
                class=""
        >
          <option
                  v-for="label in labels.matchTypes"
                  :key="label.id"
                  :value="label.id"
          >
            {{ label.label }}
          </option>
        </select>
      </div>

        <button
                v-if="depth > 1"
                type="button"
                class="btn"
                @click="remove"
                v-html="labels.removeGroup"
        >
        </button>
      </div>
    </div>

    <div class="conditionbuilder-group-body">
      <div class="rule-actions">
        <div class="">
          <button
                  v-if="depth < maxDepth"
                  type="button"
                  class="btn"
                  @click="addGroup"
          >
            {{ labels.addGroup }}
          </button>
        </div>
      </div>

      <condition-builder-children v-bind="$props"/>

      <div class="select">
        <select
                v-model="selectedRule"
                class=""
        >
          <option
                  v-for="rule in rules"
                  :key="rule.id"
                  :value="rule"
          >
            {{ rule.label }}
          </option>
        </select>
      </div>

      <button
              type="button"
              class="btn"
              @click="addRule"
      >
        {{labels.addRule}}
      </button>


    </div>
  </div>
</template>

<script>
    import ConditionBuilderGroup from '../../components/ConditionBuilderGroup';
    import CraftCpRule from './CraftCpRule.vue';

    export default {
        name: "ConditionBuilderGroup",

        components: {
            // eslint-disable-next-line vue/no-unused-components
            'ConditionBuilderRule': CraftCpRule
        },

        extends: ConditionBuilderGroup,
    }
</script>

<style>
  .vue-condition-builder .btn{
    margin: 5px;
  }

  .vue-condition-builder .conditionbuilder-group .rule-actions {
    margin-bottom: 20px;
  }

  .vue-condition-builder .conditionbuilder-rule {
    margin-top: 5px;
    margin-bottom: 5px;
    padding: 5px;
  }

  .vue-condition-builder .conditionbuilder-group.depth-1 .conditionbuilder-rule,
  .vue-condition-builder .conditionbuilder-group.depth-2 {
    border-left: 2px solid #8bc34a;
  }

  .vue-condition-builder .conditionbuilder-group.depth-2 .conditionbuilder-rule,
  .vue-condition-builder .conditionbuilder-group.depth-3 {
    border-left: 2px solid #00bcd4;
    margin-left: 20px;
  }

  .vue-condition-builder .conditionbuilder-group.depth-3 .conditionbuilder-rule,
  .vue-condition-builder .conditionbuilder-group.depth-4 {
    border-left: 2px solid #ff5722;
    margin-left: 20px;
  }
</style>