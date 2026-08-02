/**
 * Generate thin Vue wrapper components for Craft CMS web components that need v-model support.
 *
 * This script generates:
 * 1. Vue SFC wrappers for Lion-based form components (src/vue/)
 * 2. Vue type augmentations for ALL craft-* web components (src/vue/craft-elements.d.ts)
 *
 * Usage (from packages/craftcms-ui/):
 *   node scripts/generate-vue-wrappers.js
 *
 * The generated wrappers handle v-model bridging between Vue's reactivity system and
 * each component's modelValue/checked property + model-value-changed event pattern.
 */

import {mkdirSync, writeFileSync} from 'fs';
import {dirname, resolve} from 'path';
import {fileURLToPath} from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');
const VUE_DIR = resolve(ROOT, 'dist/vue');

// ─── Component Definitions ──────────────────────────────────────────────────

/**
 * Components that expose their writable state through modelValue.
 * v-model maps to `.modelValue` property and `model-value-changed` event.
 */
const VALUE_COMPONENTS = [
  {
    tagName: 'craft-input',
    className: 'CraftInput',
    fileName: 'CraftInput',
    modelType: 'string | number',
    importPath: '../components/input/input',
    slots: [
      'label',
      'help-text',
      'input',
      'feedback',
      'prefix',
      'suffix',
      'before',
      'after',
    ],
  },
  {
    tagName: 'craft-input-color',
    className: 'CraftInputColor',
    fileName: 'CraftInputColor',
    modelType: 'string',
    importPath: '../components/input-color/input-color',
    slots: [
      'label',
      'help-text',
      'input',
      'feedback',
      'prefix',
      'suffix',
      'before',
      'after',
    ],
  },
  {
    tagName: 'craft-input-handle',
    className: 'CraftInputHandle',
    fileName: 'CraftInputHandle',
    modelType: 'string',
    importPath: '../components/input-handle/input-handle',
    slots: [
      'label',
      'help-text',
      'input',
      'feedback',
      'prefix',
      'suffix',
      'before',
      'after',
    ],
  },
  {
    tagName: 'craft-input-password',
    className: 'CraftInputPassword',
    fileName: 'CraftInputPassword',
    modelType: 'string',
    importPath: '../components/input-password/input-password',
    slots: [
      'label',
      'help-text',
      'input',
      'feedback',
      'prefix',
      'suffix',
      'before',
      'after',
    ],
  },
  {
    tagName: 'craft-input-money',
    className: 'CraftInputMoney',
    fileName: 'CraftInputMoney',
    modelType: 'string | number',
    importPath: '../components/input-money/input-money',
    slots: [
      'label',
      'help-text',
      'input',
      'feedback',
      'prefix',
      'suffix',
      'before',
      'after',
    ],
  },
  {
    tagName: 'craft-textarea',
    className: 'CraftTextarea',
    fileName: 'CraftTextarea',
    modelType: 'string',
    importPath: '../components/textarea/textarea',
    slots: [
      'label',
      'help-text',
      'input',
      'feedback',
      'prefix',
      'suffix',
      'before',
      'after',
    ],
  },
  {
    tagName: 'craft-select',
    className: 'CraftSelect',
    fileName: 'CraftSelect',
    modelType: 'string | number | boolean | null',
    modelTypeImports: ['SelectItem'],
    importPath: '../components/select/select',
    properties: [
      {name: 'options', type: 'SelectItem[]'},
      {name: 'readonly', target: 'disabled', type: 'boolean'},
    ],
    slots: [
      'label',
      'help-text',
      'input',
      'feedback',
      'prefix',
      'suffix',
      'before',
      'after',
    ],
  },
  {
    tagName: 'craft-select-rich',
    className: 'CraftSelectRich',
    fileName: 'CraftSelectRich',
    modelType: 'string',
    importPath: '../components/select-rich/select-rich',
    slots: [
      'label',
      'help-text',
      'input',
      'feedback',
      'prefix',
      'suffix',
      'before',
      'after',
    ],
  },
  {
    tagName: 'craft-combobox',
    className: 'CraftCombobox',
    fileName: 'CraftCombobox',
    modelType: 'string',
    importPath: '../components/combobox/combobox',
    slots: [
      'label',
      'help-text',
      'input',
      'feedback',
      'prefix',
      'suffix',
      'before',
      'after',
    ],
  },
  {
    tagName: 'craft-input-file',
    className: 'CraftInputFile',
    fileName: 'CraftInputFile',
    modelType: 'File[]',
    importPath: '../components/input-file/input-file',
    slots: [
      'label',
      'help-text',
      'input',
      'feedback',
      'prefix',
      'suffix',
      'before',
      'after',
      'file-select-button',
      'selected-file-list',
    ],
  },
  {
    // Plain LitElement wrapping <craft-select-rich>. Its own modelValue is now
    // kept in sync with the inner select and it re-dispatches a composed
    // `model-value-changed`, so the standard value-wrapper works. Options are
    // supplied via the default slot; the label is an attribute (not a slot).
    tagName: 'craft-select-color',
    className: 'CraftSelectColor',
    fileName: 'CraftSelectColor',
    modelType: 'string | null',
    importPath: '../components/select-color/select-color',
    slots: ['feedback'],
  },
  {
    tagName: 'craft-object-select',
    className: 'CraftObjectSelect',
    fileName: 'CraftObjectSelect',
    modelType: 'unknown[]',
    modelDefault: '[]',
    importPath: '../components/object-select/object-select',
  },
  {
    tagName: 'craft-option-rows',
    className: 'CraftOptionRows',
    fileName: 'CraftOptionRows',
    modelType: 'OptionRow[]',
    modelTypeImports: ['OptionRow'],
    modelDefault: '[]',
    importPath: '../components/option-rows/option-rows',
  },
  {
    tagName: 'craft-editable-table',
    className: 'CraftEditableTable',
    fileName: 'CraftEditableTable',
    modelType: 'EditableTableValue',
    modelTypeImports: [
      'EditableTableColumn',
      'EditableTableFixedRow',
      'EditableTableRow',
      'EditableTableValue',
    ],
    modelDefault: '[]',
    importPath: '../components/editable-table/editable-table',
    properties: [
      {name: 'sourceName', type: 'string', defaultValue: 'undefined'},
      {name: 'columns', type: 'EditableTableColumn[]', defaultValue: '[]'},
      {
        name: 'fixedRows',
        type: 'EditableTableFixedRow[]',
        defaultValue: '[]',
      },
      {name: 'defaultRow', type: 'EditableTableRow', defaultValue: '{}'},
      {name: 'addRowLabel', type: 'string', defaultValue: 'undefined'},
      {name: 'keyed', type: 'boolean', defaultValue: 'false'},
      {name: 'includeRowId', type: 'boolean', defaultValue: 'false'},
      {name: 'definesColumns', type: 'boolean', defaultValue: 'false'},
      {name: 'columnsFrom', type: 'string', defaultValue: 'undefined'},
      {
        name: 'readonly',
        target: 'readOnly',
        type: 'boolean',
        defaultValue: 'false',
      },
    ],
  },
];

/**
 * Components that use checked (boolean-based value).
 * v-model maps to `.checked` property and `model-value-changed` event.
 */
const CHECKED_COMPONENTS = [
  {
    tagName: 'craft-switch',
    className: 'CraftSwitch',
    fileName: 'CraftSwitch',
    modelType: 'boolean | null',
    importPath: '../components/switch/switch',
    readOnlyAsDisabled: true,
    properties: [
      {name: 'label', type: 'string'},
      {name: 'onLabel', type: 'string'},
      {name: 'offLabel', type: 'string'},
      {name: 'size', type: "'small' | 'medium'"},
    ],
    slots: ['label', 'help-text', 'input', 'feedback'],
  },
  {
    tagName: 'craft-checkbox',
    className: 'CraftCheckbox',
    fileName: 'CraftCheckbox',
    importPath: '../components/checkbox/checkbox',
    readOnlyAsDisabled: true,
    slots: ['label', 'help-text', 'input'],
  },
];

/**
 * Group components whose modelValue is an aggregation of child choiceValues.
 * v-model maps to `.modelValue` property and `model-value-changed` event.
 */
const GROUP_COMPONENTS = [
  {
    tagName: 'craft-checkbox-group',
    className: 'CraftCheckboxGroup',
    fileName: 'CraftCheckboxGroup',
    modelType: 'string[]',
    importPath: '../components/checkbox-group/checkbox-group',
    slots: ['label', 'help-text', 'feedback'],
  },
  {
    tagName: 'craft-radio-group',
    className: 'CraftRadioGroup',
    fileName: 'CraftRadioGroup',
    modelType: 'string',
    importPath: '../components/radio-group/radio-group',
    slots: ['label', 'help-text', 'feedback'],
  },
];

/**
 * Chrome-only components with no model value of their own. The wrapper only
 * bridges an `error` prop into the feedback slot; everything else (including
 * the wrapped control) passes through via $attrs and the default slot.
 */
const CHROME_COMPONENTS = [
  {
    tagName: 'craft-field',
    className: 'CraftField',
    fileName: 'CraftField',
    importPath: '../components/field/field',
  },
];

/**
 * Select rich component — uses modelValue like VALUE_COMPONENTS but needs
 * a custom wrapper template for additional behaviour.
 */
const SELECT_RICH_COMPONENT = {
  tagName: 'craft-select-rich',
  className: 'CraftSelectRich',
  fileName: 'CraftSelectRich',
  modelType: 'string',
  importPath: '../components/select-rich/select-rich',
};

/**
 * Combobox — uses modelValue like VALUE_COMPONENTS but needs a custom wrapper
 * that passes `options` through as an array property (so the web component can
 * filter and cap rendering itself).
 */
const COMBOBOX_COMPONENT = {
  tagName: 'craft-combobox',
  className: 'CraftCombobox',
  fileName: 'CraftCombobox',
  // Values are strings at the DOM level, but consumers bind number/boolean refs
  // for env-backed fields; keep the model type permissive to match.
  modelType: 'string | number | boolean',
  importPath: '../components/combobox/combobox',
};

// ─── Template Generators ────────────────────────────────────────────────────

/**
 * Actually, using Vue's slot forwarding with web component slots is tricky.
 * Web components use <slot> natively, not Vue's slot system.
 * The wrappers need to use a simpler approach: render the web component
 * and let consumers put slotted content directly as children.
 *
 * The wrapper's job is ONLY to bridge v-model. Everything else passes through
 * via $attrs and default slot.
 */

function generateValueWrapper(component) {
  const modelTypeImports = component.modelTypeImports?.length
    ? `\n  import type {${component.modelTypeImports.join(', ')}} from '${component.importPath}.ts.mjs';`
    : '';
  const properties = (component.properties ?? [])
    .map(({name, type}) => `    ${name}?: ${type}\n`)
    .join('');
  const propertyBindings = (component.properties ?? [])
    .map(
      ({name, target, defaultValue}) =>
        `    .${target ?? name}="props.${name}${defaultValue === undefined ? '' : ` ?? ${defaultValue}`}"\n`
    )
    .join('');
  const propsDeclaration = component.properties?.length
    ? `  const props = defineProps<{\n    error?: null | string\n${properties}  }>()`
    : `  defineProps<{\n    error?: null | string\n  }>()`;

  return `<!--
  Auto-generated Vue wrapper for <${component.tagName}>
  Provides v-model support by bridging Vue's modelValue to the web component's modelValue property.
  Generated by: scripts/generate-vue-wrappers.js
-->
<script setup lang="ts">
  import type ${component.className} from '${component.importPath}.ts.mjs';${modelTypeImports}

  defineOptions({
    name: '${component.className}',
  });

  const model = defineModel<${component.modelType}>(${component.modelDefault === undefined ? '' : `{default: () => (${component.modelDefault})}`});

${propsDeclaration}

  function onModelValueChanged(event: Event) {
    // Ignore changes emitted while Vue is applying initial properties, plus
    // non-user synchronization events. Both can otherwise clobber the
    // bound value while the component is mounting or materializing options.
    if (
      !(event.target as ${component.className}).isConnected ||
      (event as CustomEvent).detail?.initialize ||
      (event as CustomEvent).detail?.isTriggeredByUser === false
    ) {
      return;
    }
    model.value = (event.target as ${component.className}).modelValue;
  }
</script>

<template>
  <${component.tagName}
${propertyBindings}    .modelValue="model"
    @model-value-changed="onModelValueChanged"
    :has-feedback-for="error ? 'error' : ''"
  >
    <slot></slot>

    <div slot="feedback">
      <ul class="error-list" v-if="error">
        <li>{{ error }}</li>
      </ul>
    </div>
  </${component.tagName}>
</template>
`;
}

function generateCheckedWrapper(component) {
  const properties = (component.properties ?? [])
    .map(({name, type}) => `    ${name}?: ${type}\n`)
    .join('');
  const propertyBindings = (component.properties ?? [])
    .map(({name}) => `    .${name}="props.${name}"\n`)
    .join('');

  return `<!--
  Auto-generated Vue wrapper for <${component.tagName}>
  Provides v-model support by bridging Vue's modelValue to Lion UI's checked property.
  Generated by: scripts/generate-vue-wrappers.js
-->
<script setup lang="ts">
  import type ${component.className} from '${component.importPath}.ts.mjs';

  defineOptions({
    name: '${component.className}',
  });
  
  const props = defineProps<{
    error?: null | string
    modelValue?: ${component.modelType ?? 'boolean'}
${component.readOnlyAsDisabled ? '    readonly?: boolean\n' : ''}${properties}  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
  }>();

  function onModelValueChanged(event: Event) {
    // Ignore Lion's initial model-value-changed (detail.initialize=true) so the
    // element's default value can't clobber the bound value on mount.
    if ((event as CustomEvent).detail?.initialize) {
      return;
    }
    emit('update:modelValue', (event.target as ${component.className})?.checked ?? false);
  }
</script>

<template>
  <${component.tagName}
    .checked="props.modelValue ?? false"
${component.readOnlyAsDisabled ? '    .disabled="props.readonly ?? false"\n' : ''}${propertyBindings}    @model-value-changed="onModelValueChanged"
    :has-feedback-for="error ? 'error' : ''"
  >
    <slot></slot>

    <div slot="feedback">
      <ul class="error-list" v-if="error">
        <li>{{ error }}</li>
      </ul>
    </div>
  </${component.tagName}>
</template>
`;
}

function generateGroupWrapper(component) {
  return `<!--
  Auto-generated Vue wrapper for <${component.tagName}>
  Provides v-model support by bridging Vue's modelValue to Lion UI's group modelValue.
  Generated by: scripts/generate-vue-wrappers.js
-->
<script setup lang="ts">
  import type ${component.className} from '${component.importPath}.ts.mjs';

  defineOptions({
    name: '${component.className}',
  });

  defineProps<{
    error?: null | string
  }>()

  const model = defineModel<${component.modelType}>();

  function onModelValueChanged(event: Event) {
    // Ignore Lion's initial model-value-changed (detail.initialize=true) so the
    // element's default value can't clobber the bound value on mount.
    if ((event as CustomEvent).detail?.initialize) {
      return;
    }
    model.value = (event.target as ${component.className})?.modelValue ?? undefined;
  }
</script>

<template>
  <${component.tagName}
    .modelValue="model"
    @model-value-changed="onModelValueChanged"
    :has-feedback-for="error ? 'error' : ''"
  >
    <slot></slot>

    <div slot="feedback">
      <ul class="error-list" v-if="error">
        <li>{{ error }}</li>
      </ul>
    </div>
  </${component.tagName}>
</template>
`;
}

function generateChromeWrapper(component) {
  return `<!--
  Auto-generated Vue wrapper for <${component.tagName}>
  Chrome-only shell (no v-model); bridges an error prop into the feedback slot.
  Generated by: scripts/generate-vue-wrappers.js
-->
<script setup lang="ts">
  defineOptions({
    name: '${component.className}',
  });

  defineProps<{
    error?: null | string
  }>()
</script>

<template>
  <${component.tagName} v-bind="$attrs">
    <slot></slot>

    <div slot="feedback">
      <ul class="error-list" v-if="error">
        <li>{{ error }}</li>
      </ul>
    </div>
  </${component.tagName}>
</template>
`;
}

function generateSelectRichWrapper(component) {
  return `<!--
  Auto-generated Vue wrapper for <${component.tagName}>
  Provides v-model support by bridging Vue's modelValue to Lion UI's modelValue property.
  Generated by: scripts/generate-vue-wrappers.js
-->
<script setup lang="ts">
  import type ${component.className} from '${component.importPath}.ts.mjs';

  export interface SelectRichOption {
    label: string;
    value: string | number;
  }

  defineOptions({
    name: '${component.className}',
  });
  
  const model = defineModel<${component.modelType}>();

  defineProps<{
    error?: null | string
    options?: SelectRichOption[]
  }>()

  function onModelValueChanged(event: Event) {
    // Ignore Lion's initial model-value-changed (detail.initialize=true) so the
    // element's default value can't clobber the bound value on mount.
    if ((event as CustomEvent).detail?.initialize) {
      return;
    }
    model.value = (event.target as ${component.className})?.modelValue;
  }
</script>

<template>
  <${component.tagName}
    .modelValue="model"
    @model-value-changed="onModelValueChanged"
    :has-feedback-for="error ? 'error' : ''"
  >
    <craft-option
      v-for="option in options"
      :key="option.value"
      .choiceValue="String(option.value)"
    >
      <slot name="option" :option="option">
        {{ option.label }}
      </slot>
    </craft-option>

    <div slot="feedback">
      <ul class="error-list" v-if="error">
        <li>{{ error }}</li>
      </ul>
    </div>
  </${component.tagName}>
</template>
`;
}

function generateComboboxWrapper(component) {
  return `<!--
  Auto-generated Vue wrapper for <${component.tagName}>
  Provides v-model support and passes options through as an array property so
  the web component can filter and cap rendering itself (large-list performance).
  Generated by: scripts/generate-vue-wrappers.js
-->
<script setup lang="ts">
  import type ${component.className} from '${component.importPath}.ts.mjs';

  export interface ComboboxOption {
    label: string;
    value: string;
    type?: 'option';
    data?: Record<string, any> | null;
  }

  export interface ComboboxOptGroup {
    type: 'optgroup';
    label: string;
    options: ComboboxOption[];
  }

  export type ComboboxItem = ComboboxOption | ComboboxOptGroup;

  defineOptions({
    name: '${component.className}',
  });

  const model = defineModel<${component.modelType}>();

  withDefaults(
    defineProps<{
      error?: null | string;
      options?: ComboboxItem[];
      requireOptionMatch?: boolean;
      showAllOnEmpty?: boolean;
      clearable?: boolean;
      limit?: number;
    }>(),
    {
      options: () => [],
      requireOptionMatch: false,
      showAllOnEmpty: false,
      clearable: false,
      limit: 150,
    }
  );

  function onModelValueChanged(event: Event) {
    // Lion fires an initial model-value-changed with detail.initialize=true and
    // its default (empty) value while the element boots — before Vue's
    // .modelValue binding has settled. Honoring that flag (as Lion's own
    // form-group repropagation does) prevents it from clobbering a bound value.
    if ((event as CustomEvent).detail?.initialize) {
      return;
    }
    model.value = (event.target as ${component.className})?.modelValue ?? undefined;
  }
</script>

<template>
  <${component.tagName}
    .options="options"
    .requireOptionMatch="requireOptionMatch"
    .showAllOnEmpty="showAllOnEmpty"
    .clearable="clearable"
    .limit="limit"
    .modelValue="model"
    @model-value-changed="onModelValueChanged"
    :has-feedback-for="error ? 'error' : ''"
  >
    <slot></slot>

    <div slot="feedback">
      <ul class="error-list" v-if="error">
        <li>{{ error }}</li>
      </ul>
    </div>
  </${component.tagName}>
</template>
`;
}

// ─── Declaration File Generators ────────────────────────────────────────────

/**
 * Generate a `.vue.d.ts` declaration file for a Vue wrapper component.
 * TypeScript will use this instead of processing the `.vue` source, avoiding
 * broken relative imports (e.g. `../components/input/input`) in the dist folder.
 */
function generateValueDeclaration(component) {
  const modelTypeImports = component.modelTypeImports?.length
    ? `import type {${component.modelTypeImports.join(', ')}} from '${component.importPath}.ts.mjs';\n`
    : '';
  const properties = (component.properties ?? [])
    .map(({name, type}) => `  ${name}?: ${type};\n`)
    .join('');

  return `/**
 * Auto-generated type declaration for ${component.fileName}.vue
 * Generated by: scripts/generate-vue-wrappers.js
 */
import type {DefineComponent} from 'vue';
${modelTypeImports}declare const _default: DefineComponent<{
  error?: string | null;
  modelValue?: ${component.modelType};
${properties}  'onUpdate:modelValue'?: (val: ${component.modelType}) => void;
}>;
export default _default;
`;
}

function generateCheckedDeclaration(component) {
  const properties = (component.properties ?? [])
    .map(({name, type}) => `  ${name}?: ${type};\n`)
    .join('');

  return `/**
 * Auto-generated type declaration for ${component.fileName}.vue
 * Generated by: scripts/generate-vue-wrappers.js
 */
import type {DefineComponent} from 'vue';
declare const _default: DefineComponent<{
  error?: string | null;
  modelValue?: ${component.modelType ?? 'boolean'};
${component.readOnlyAsDisabled ? '  readonly?: boolean;\n' : ''}${properties}  'onUpdate:modelValue'?: (val: boolean) => void;
}>;
export default _default;
`;
}

function generateChromeDeclaration(component) {
  return `/**
 * Auto-generated type declaration for ${component.fileName}.vue
 * Generated by: scripts/generate-vue-wrappers.js
 */
import type {DefineComponent} from 'vue';
declare const _default: DefineComponent<{
  error?: string | null;
}>;
export default _default;
`;
}

function generateGroupDeclaration(component) {
  return `/**
 * Auto-generated type declaration for ${component.fileName}.vue
 * Generated by: scripts/generate-vue-wrappers.js
 */
import type {DefineComponent} from 'vue';
declare const _default: DefineComponent<{
  error?: string | null;
  modelValue?: ${component.modelType};
  'onUpdate:modelValue'?: (val: ${component.modelType}) => void;
}>;
export default _default;
`;
}

// ─── Type Augmentation Generator ────────────────────────────────────────────

/**
 * All craft-* web components with their class names and import paths.
 * Used to generate Vue IntrinsicElements type augmentations.
 */
const ALL_COMPONENTS = [
  // Form components (value-based)
  ...VALUE_COMPONENTS,
  // Form components (checked-based)
  ...CHECKED_COMPONENTS,
  // Form components (groups)
  ...GROUP_COMPONENTS,
  // Field chrome shell
  ...CHROME_COMPONENTS,
  // Choice inputs used inside groups (not typically needing v-model wrappers)
  {
    tagName: 'craft-radio',
    className: 'CraftRadio',
    importPath: '../components/radio/radio',
  },
  {
    tagName: 'craft-checkbox-indeterminate',
    className: 'CraftCheckboxIndeterminate',
    importPath: '../components/checkbox-indeterminate/checkbox-indeterminate',
  },
  // Select rich
  {
    tagName: 'craft-select-rich',
    className: 'CraftSelectRich',
    importPath: '../components/select-rich/select-rich',
  },
  // Display components
  {
    tagName: 'craft-button',
    className: 'CraftButton',
    importPath: '../components/button/button',
  },
  {
    tagName: 'craft-button-group',
    className: 'CraftButtonGroup',
    importPath: '../components/button-group/button-group',
  },
  {
    tagName: 'craft-icon',
    className: 'CraftIcon',
    importPath: '../components/icon/icon',
  },
  {
    tagName: 'craft-option',
    className: 'CraftOption',
    importPath: '../components/option/option',
  },
  {
    tagName: 'craft-action-menu',
    className: 'CraftActionMenu',
    importPath: '../components/action-menu/action-menu',
  },
  {
    tagName: 'craft-action-item',
    className: 'CraftActionItem',
    importPath: '../components/action-item/action-item',
  },
  {
    tagName: 'craft-avatar',
    className: 'CraftAvatar',
    importPath: '../components/avatar/avatar',
  },
  {
    tagName: 'craft-badge-indicator',
    className: 'CraftBadgeIndicator',
    importPath: '../components/badge-indicator/badge-indicator',
  },
  {
    tagName: 'craft-breadcrumbs',
    className: 'CraftBreadcrumbs',
    importPath: '../components/breadcrumbs/breadcrumbs',
  },
  {
    tagName: 'craft-breadcrumb-item',
    className: 'CraftBreadcrumbItem',
    importPath: '../components/breadcrumb-item/breadcrumb-item',
  },
  {
    tagName: 'craft-callout',
    className: 'CraftCallout',
    importPath: '../components/callout/callout',
  },
  {
    tagName: 'craft-card',
    className: 'CraftCard',
    importPath: '../components/card/card',
  },
  {
    tagName: 'craft-chip',
    className: 'CraftChip',
    importPath: '../components/chip/chip',
  },
  {
    tagName: 'craft-copy-attribute',
    className: 'CraftCopyAttribute',
    importPath: '../components/copy-attribute/copy-attribute',
  },
  {
    tagName: 'craft-copy-button',
    className: 'CraftCopyButton',
    importPath: '../components/copy-button/copy-button',
  },
  {
    tagName: 'craft-dialog',
    className: 'CraftDialog',
    importPath: '../components/dialog/dialog',
  },
  {
    tagName: 'craft-disclosure',
    className: 'CraftDisclosure',
    importPath: '../components/disclosure/disclosure',
  },
  {
    tagName: 'craft-indicator',
    className: 'CraftIndicator',
    importPath: '../components/indicator/indicator',
  },
  {
    tagName: 'craft-nav-list',
    className: 'CraftNavList',
    importPath: '../components/nav-list/nav-list',
  },
  {
    tagName: 'craft-nav-item',
    className: 'CraftNavItem',
    importPath: '../components/nav-item/nav-item',
  },
  {
    tagName: 'craft-popover',
    className: 'CraftPopover',
    importPath: '../components/popover/popover',
  },
  {
    tagName: 'craft-progress',
    className: 'CraftProgress',
    importPath: '../components/progress/progress',
  },
  {
    tagName: 'craft-progress-bar',
    className: 'CraftProgressBar',
    importPath: '../components/progress-bar/progress-bar',
  },
  {
    tagName: 'craft-shortcut',
    className: 'CraftShortcut',
    importPath: '../components/shortcut/shortcut',
  },
  {
    tagName: 'craft-spinner',
    className: 'CraftSpinner',
    importPath: '../components/spinner/spinner',
  },
  {
    tagName: 'craft-status',
    className: 'CraftStatus',
    importPath: '../components/status/status',
  },
  {
    tagName: 'craft-switch-button',
    className: 'CraftSwitchButton',
    importPath: '../components/switch-button/switch-button',
  },
  {
    tagName: 'craft-tab',
    className: 'CraftTab',
    importPath: '../components/tab/tab',
  },
  {
    tagName: 'craft-tabs',
    className: 'CraftTabs',
    importPath: '../components/tabs/tabs',
  },
  {
    tagName: 'craft-selected-file-list',
    className: 'CraftSelectedFileList',
    importPath: '../components/input-file/selected-file-list',
  },
  {
    tagName: 'craft-tooltip',
    className: 'CraftTooltip',
    importPath: '../components/tooltip/tooltip',
  },
];

function generateTypeAugmentations() {
  const imports = ALL_COMPONENTS.map(
    (c) => `import type ${c.className} from '${c.importPath}';`
  ).join('\n');

  const entries = ALL_COMPONENTS.map(
    (c) => `      '${c.tagName}': ${c.className};`
  ).join('\n');

  return `/**
 * Vue type augmentations for Craft CMS web components.
 * Provides IntrinsicElements declarations so Vue templates get
 * type-checking and autocomplete for all craft-* custom elements.
 *
 * Auto-generated by: scripts/generate-vue-wrappers.js
 */

${imports}

declare module 'vue' {
  interface IntrinsicElements {
${entries}
  }
}

export {};
`;
}

// ─── Main ───────────────────────────────────────────────────────────────────

export default function main() {
  // Ensure output directory exists
  mkdirSync(VUE_DIR, {recursive: true});

  let count = 0;

  // Generate value-based wrappers
  for (const component of VALUE_COMPONENTS) {
    const content = generateValueWrapper(component);
    const filePath = resolve(VUE_DIR, `${component.fileName}.vue`);
    writeFileSync(filePath, content);
    const declContent = generateValueDeclaration(component);
    const declPath = resolve(VUE_DIR, `${component.fileName}.vue.d.ts`);
    writeFileSync(declPath, declContent);
    console.log(`  Generated: ${VUE_DIR}/${component.fileName}.vue`);
    count++;
  }

  // Generate checked-based wrappers
  for (const component of CHECKED_COMPONENTS) {
    const content = generateCheckedWrapper(component);
    const filePath = resolve(VUE_DIR, `${component.fileName}.vue`);
    writeFileSync(filePath, content);
    const declContent = generateCheckedDeclaration(component);
    const declPath = resolve(VUE_DIR, `${component.fileName}.vue.d.ts`);
    writeFileSync(declPath, declContent);
    console.log(`  Generated: ${VUE_DIR}/${component.fileName}.vue`);
    count++;
  }

  // Generate group wrappers
  for (const component of GROUP_COMPONENTS) {
    const content = generateGroupWrapper(component);
    const filePath = resolve(VUE_DIR, `${component.fileName}.vue`);
    writeFileSync(filePath, content);
    const declContent = generateGroupDeclaration(component);
    const declPath = resolve(VUE_DIR, `${component.fileName}.vue.d.ts`);
    writeFileSync(declPath, declContent);
    console.log(`  Generated: ${VUE_DIR}/${component.fileName}.vue`);
    count++;
  }

  // Generate chrome-only wrappers
  for (const component of CHROME_COMPONENTS) {
    const content = generateChromeWrapper(component);
    const filePath = resolve(VUE_DIR, `${component.fileName}.vue`);
    writeFileSync(filePath, content);
    const declContent = generateChromeDeclaration(component);
    const declPath = resolve(VUE_DIR, `${component.fileName}.vue.d.ts`);
    writeFileSync(declPath, declContent);
    console.log(`  Generated: ${VUE_DIR}/${component.fileName}.vue`);
    count++;
  }

  // Generate select-rich wrapper
  {
    const component = SELECT_RICH_COMPONENT;
    const content = generateSelectRichWrapper(component);
    const filePath = resolve(VUE_DIR, `${component.fileName}.vue`);
    writeFileSync(filePath, content);
    const declContent = generateValueDeclaration(component);
    const declPath = resolve(VUE_DIR, `${component.fileName}.vue.d.ts`);
    writeFileSync(declPath, declContent);
    console.log(`  Generated: ${VUE_DIR}/${component.fileName}.vue`);
    count++;
  }

  // Generate combobox wrapper (overwrites the value wrapper written above)
  {
    const component = COMBOBOX_COMPONENT;
    const content = generateComboboxWrapper(component);
    const filePath = resolve(VUE_DIR, `${component.fileName}.vue`);
    writeFileSync(filePath, content);
    const declContent = generateValueDeclaration(component);
    const declPath = resolve(VUE_DIR, `${component.fileName}.vue.d.ts`);
    writeFileSync(declPath, declContent);
    console.log(`  Generated: ${VUE_DIR}/${component.fileName}.vue`);
    count++;
  }

  console.log(`\n  ${count} Vue wrappers generated in ${VUE_DIR}/`);

  // Generate type augmentations
  const types = generateTypeAugmentations();
  const typesPath = resolve(VUE_DIR, 'craft-elements.d.ts');
  writeFileSync(typesPath, types);
  console.log(`  Generated: ${VUE_DIR}/craft-elements.d.ts`);

  console.log('\nDone!');
}

main();
