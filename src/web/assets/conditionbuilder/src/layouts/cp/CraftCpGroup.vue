<template>
  <!-- eslint-disable vue/no-v-html -->
  <div class="condition-builder-group" :class="getGroupContainerClasses(depth)">

    <div :class="getGroupClasses(depth)">

      <div class="condition-builder-group-header flex">

        <template v-if="!groupOperatorEnabled">
          <div class="tw-flex-grow">
            <div class="tw-inline-block tw-px-2 tw-py-1 tw-bg-gray-200" :class="getGroupInputDepthBorderClass(depth)">
              {{ query.logicalOperator.toUpperCase() }}
            </div>
          </div>
        </template>

        <template v-if="groupOperatorEnabled">
          <div class="tw-flex-grow">
          <div class="tw-inline-block" :class="getGroupInputDepthBorderClass(depth)">
            <div class="select">
              <select class="condition-builder-match-type-select" style="margin-left: -4px">
                v-model="query.logicalOperator">
                <option v-for="label in labels.matchTypes"
                        :key="label.id"
                        :value="label.id">
                  {{ label.label }}
                </option>
              </select>

            </div>
          </div>
          </div>
        </template>


          <template v-if="depth > 1">
            <div>
            <a @click="remove" v-bind:title="labels.removeGroup" role="button" href="#"
               class="delete icon tw-p-3"></a>
            </div>
          </template>

      </div>

      <condition-builder-children v-bind="$props"/>

      <div class="condition-builder-group-footer tw-my-2 flex">

        <div :class="getGroupInputDepthBorderClass(depth)">

        <div class="select" style="margin-left: -4px">
          <select v-model="selectedRule">
            <option
                    v-for="rule in availableRules"
                    :key="rule.id"
                    :value="rule"
            >
              {{ rule.label }}
            </option>
          </select>
        </div>
        </div>

        <div class="tw-inline-block"
             :class="getGroupInputDepthBorderClass(depth)"
             v-if="availableRules.length >= 1">
          <button type="button"
                  class="btn add icon"
                  @click="addRule"
                  style="margin-left: -4px"
          >
            {{labels.addRule}}
          </button>
        </div>

        <template v-if="depth < maxDepth">
          <div class="tw-inline-block"
               :class="getGroupInputDepthBorderClass(depth)">
            <button type="button"
                    class="btn add icon"
                    @click="addGroup"
                    style="margin-left: -4px"
            >
              {{labels.addGroup}}
            </button>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
    import ConditionBuilderGroup from '../../components/ConditionBuilderGroup';
    import CraftCpRule from './CraftCpRule.vue';
    import Mixins from '../../mixins/mixins.js';

    export default {
        name: "ConditionBuilderGroup",
        mixins: [Mixins],
        components: {
            // eslint-disable-next-line vue/no-unused-components
            'ConditionBuilderRule': CraftCpRule
        },

        extends: ConditionBuilderGroup,
    }
</script>

<style lang="scss">
</style>
