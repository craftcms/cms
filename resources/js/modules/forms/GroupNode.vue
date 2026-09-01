<script setup lang="ts">
  import '@craftcms/ui/components/field/field';
  import '@craftcms/ui/components/field-group/field-group';
  import '@craftcms/ui/components/disclosure/disclosure';
  import FormNodeList from './FormNodeList.vue';
  import type {FormChange, FormNodePayload, FormPayload} from './types';

  type GroupNodeProps = {
    label?: string | null;
    collapsible?: boolean;
    /** Renders the group as one field rather than a section — see `Nodes\Group`. */
    asField?: boolean;
    instructions?: string | null;
    tip?: string;
    tipHtml?: string;
    warning?: string;
    warningHtml?: string;
    width?: number;
    /** Hidden from view; children still resolve and still hold their values. */
    hidden?: boolean;
  };

  const props = defineProps<{
    node: FormNodePayload<GroupNodeProps>;
    values: FormPayload['values'];
    errors: FormPayload['errors'];
    touchedPaths: Set<string>;
    scope: string[];
    refreshable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'change', change: FormChange): void;
  }>();
</script>

<template>
  <craft-field
    v-if="node.props.asField"
    fieldset
    :label="node.props.label ?? undefined"
    :help-text="node.props.instructions ?? undefined"
    :class="{
      [`width-${node.props.width}`]: Boolean(node.props.width),
      hidden: Boolean(node.props.hidden),
    }"
    :hidden="node.props.hidden || undefined"
    :data-form-node="node.uid"
  >
    <span v-if="node.props.tipHtml" slot="tip" v-html="node.props.tipHtml" />
    <span
      v-if="node.props.warningHtml"
      slot="warning"
      v-html="node.props.warningHtml"
    />
    <craft-field-group slot="input">
      <FormNodeList
        :nodes="node.children ?? []"
        :values="values"
        :errors="errors"
        :touched-paths="touchedPaths"
        :scope="scope"
        :refreshable="refreshable"
        @change="emit('change', $event)"
      />
    </craft-field-group>
  </craft-field>
  <component
    v-else
    :is="node.props.collapsible ? 'craft-disclosure' : 'fieldset'"
    :label="node.props.collapsible ? node.props.label : undefined"
    :class="{hidden: Boolean(node.props.hidden)}"
    :hidden="node.props.hidden || undefined"
    :data-form-node="node.uid"
  >
    <legend v-if="!node.props.collapsible && node.props.label">
      {{ node.props.label }}
    </legend>
    <craft-field-group :slot="node.props.collapsible ? 'content' : undefined">
      <FormNodeList
        :nodes="node.children ?? []"
        :values="values"
        :errors="errors"
        :touched-paths="touchedPaths"
        :scope="scope"
        :refreshable="refreshable"
        @change="emit('change', $event)"
      />
    </craft-field-group>
  </component>
</template>
