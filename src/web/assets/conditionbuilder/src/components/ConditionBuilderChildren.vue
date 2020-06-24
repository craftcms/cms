<template>
  <div class="condition-builder-children" :class="'depth-' + depth.toString()">
    <component
      :is="getComponent(child.type)"
      v-for="(child, index) in query.children"
      :key="index"
      :type="child.type"
      :query.sync="child.query"
      :rule-types="ruleTypes"
      :rules="rules"
      :rule="$parent.ruleById(child.query.rule)"
      :availableRules="availableRules"
      :groupOperatorEnabled="groupOperatorEnabled"
      :index="index"
      :max-depth="maxDepth"
      :depth="depth + 1"
      :labels="labels"
      @child-deletion-requested="$parent.removeChild"
    />
  </div>
</template>

<script>
export default {
  // eslint-disable-next-line vue/require-prop-types
  props: ['query', 'ruleTypes', 'rules', 'maxDepth', 'labels', 'depth', 'availableRules','groupOperatorEnabled'],

  data() {
    return {
      groupComponent: null,
      ruleComponent: null
    }
  },

  mounted() {
    this.groupComponent = this.$parent.$options.components['ConditionBuilderGroup'];
    this.ruleComponent = this.$parent.$options.components['ConditionBuilderRule'];
  },

  methods: {
    getComponent(type) {
      return type === 'condition-builder-group'
        ? this.groupComponent
        : this.ruleComponent;
    }
  }
}
</script>