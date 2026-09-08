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
 * Lion UI's modelValue/checked property + model-value-changed event pattern.
 */

import {mkdirSync, writeFileSync} from 'fs';
import {dirname, resolve} from 'path';
import {fileURLToPath} from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');
const VUE_DIR = resolve(ROOT, 'dist/vue');

// ─── Component Definitions ──────────────────────────────────────────────────

/**
 * Components that use modelValue (string-based value).
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
    tagName: 'craft-input-money',
    className: 'CraftInputMoney',
    fileName: 'CraftInputMoney',
    modelType: 'string',
    importPath: '../components/input-money/input-money',
    slots: ['label', 'help-text', 'input', 'feedback', 'before', 'after'],
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
    modelType: 'string',
    importPath: '../components/select/select',
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
    modelType: "import('../components/input-file/input-file.ts.mjs').default['modelValue']",
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
    modelType: 'string',
    importPath: '../components/select-color/select-color',
    slots: ['feedback'],
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
    slots: ['label', 'help-text', 'input', 'feedback'],
  },
  {
    tagName: 'craft-checkbox',
    className: 'CraftCheckbox',
    fileName: 'CraftCheckbox',
    importPath: '../components/checkbox/checkbox',
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
    tagName: 'craft-permission-tree',
    className: 'CraftPermissionTree',
    fileName: 'CraftPermissionTree',
    modelType: 'string[]',
    importPath: '../components/permission-tree/permission-tree',
    properties: [
      {name: 'groups', default: '() => []'},
      {name: 'lockedPermissions', default: '() => []'},
      {name: 'name', default: "''"},
      {name: 'disabled', default: 'false'},
    ],
    slots: [],
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
  return `<!--
  Auto-generated Vue wrapper for <${component.tagName}>
  Provides v-model support by bridging Vue's modelValue to Lion UI's modelValue property.
  Generated by: scripts/generate-vue-wrappers.js
-->
<script setup lang="ts">
  import type ${component.className} from '${component.importPath}.ts.mjs';

  defineOptions({
    name: '${component.className}',
  });

  const model = defineModel<${component.modelType}>();

  defineProps<{
    error?: null | string
  }>()

  function onModelValueChanged(event: Event) {
    // Ignore Lion's initial model-value-changed (detail.initialize=true), which
    // carries the element's default value before Vue's binding settles and would
    // otherwise clobber the bound value on mount.
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

function generateInputWrapper(component) {
  return `<!--
  Auto-generated Vue wrapper for <${component.tagName}>
  Provides v-model and optional text-expander support.
  Generated by: scripts/generate-vue-wrappers.js
-->
<script setup lang="ts">
  import type ${component.className} from '${component.importPath}.ts.mjs';
  import type {TextExpanderTriggers} from '../components/text-expander/text-expander.ts.mjs';
  import '../components/text-expander/text-expander.ts.mjs';
  import {useId} from 'vue';

  defineOptions({
    name: '${component.className}',
    inheritAttrs: false,
  });

  const model = defineModel<${component.modelType}>();
  const props = defineProps<{
    error?: null | string
    textExpanderTriggers?: TextExpanderTriggers
  }>()
  const inputId = useId();

  function onModelValueChanged(event: Event) {
    if ((event as CustomEvent).detail?.initialize) {
      return;
    }
    model.value = (event.target as ${component.className})?.modelValue ?? undefined;
  }
</script>

<template>
  <${component.tagName}
    v-bind="$attrs"
    .modelValue="model"
    @model-value-changed="onModelValueChanged"
    :has-feedback-for="error ? 'error' : ''"
  >
    <input v-if="props.textExpanderTriggers" :id="inputId" slot="input" />
    <slot></slot>

    <div slot="feedback">
      <ul class="error-list" v-if="error">
        <li>{{ error }}</li>
      </ul>
    </div>
  </${component.tagName}>
  <craft-text-expander
    v-if="props.textExpanderTriggers"
    :for="inputId"
    .triggers="props.textExpanderTriggers"
  />
</template>
`;
}

function generateCheckedWrapper(component) {
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
  }>()

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

function generateGroupWrapper(component) {
  const properties = component.properties ?? [];
  const props = properties
    .map(({name}) => `    ${name}?: ${component.className}['${name}']`)
    .join('\n');
  const defaults = properties
    .map(({name, default: defaultValue}) => `      ${name}: ${defaultValue},`)
    .join('\n');
  const bindings = properties
    .map(({name}) => `    .${name}="props.${name}"`)
    .join('\n');

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

  const props = withDefaults(defineProps<{
    error?: null | string
${props}
  }>(), {
${defaults}
  })

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
${bindings}
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
  const textExpanderProp =
    component.tagName === 'craft-input'
      ? `  textExpanderTriggers?: import('../components/text-expander/text-expander').TextExpanderTriggers;\n`
      : '';

  return `/**
 * Auto-generated type declaration for ${component.fileName}.vue
 * Generated by: scripts/generate-vue-wrappers.js
 */
import type {DefineComponent} from 'vue';
declare const _default: DefineComponent<{
  error?: string | null;
${textExpanderProp}
  modelValue?: ${component.modelType};
  'onUpdate:modelValue'?: (val: ${component.modelType}) => void;
}>;
export default _default;
`;
}

function generateCheckedDeclaration(component) {
  return `/**
 * Auto-generated type declaration for ${component.fileName}.vue
 * Generated by: scripts/generate-vue-wrappers.js
 */
import type {DefineComponent} from 'vue';
declare const _default: DefineComponent<{
  error?: string | null;
  modelValue?: ${component.modelType ?? 'boolean'};
  'onUpdate:modelValue'?: (val: boolean) => void;
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
  const props = (component.properties ?? [])
    .map(
      ({name}) =>
        `  ${name}?: import('${component.importPath}').default['${name}'];`
    )
    .join('\n');

  return `/**
 * Auto-generated type declaration for ${component.fileName}.vue
 * Generated by: scripts/generate-vue-wrappers.js
 */
import type {DefineComponent} from 'vue';
declare const _default: DefineComponent<{
  error?: string | null;
${props}
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
    const content =
      component.tagName === 'craft-input'
        ? generateInputWrapper(component)
        : generateValueWrapper(component);
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
