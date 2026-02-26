/**
 * Generate thin Vue wrapper components for Craft CMS web components that need v-model support.
 *
 * This script generates:
 * 1. Vue SFC wrappers for Lion-based form components (src/vue/)
 * 2. Vue type augmentations for ALL craft-* web components (src/vue/craft-elements.d.ts)
 *
 * Usage (from packages/craftcms-cp/):
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
    modelType: 'string',
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
];

/**
 * Components that use checked (boolean-based value).
 * v-model maps to `.checked` property and `checked-changed` event.
 */
const CHECKED_COMPONENTS = [
  {
    tagName: 'craft-switch',
    className: 'CraftSwitch',
    fileName: 'CraftSwitch',
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
    tagName: 'craft-radio-group',
    className: 'CraftRadioGroup',
    fileName: 'CraftRadioGroup',
    modelType: 'string',
    importPath: '../components/radio-group/radio-group',
    slots: ['label', 'help-text', 'feedback'],
  },
];

// ─── Template Generators ────────────────────────────────────────────────────

function generateSlotForwards(slots) {
  return slots
    .map(
      (slot) =>
        `    <slot name="${slot}"><template v-if="$slots['${slot}']"><template v-for="(_, name) in $slots" :key="name"><slot :name="name" v-if="name === '${slot}'"></slot></template></template></slot>`
    )
    .join('\n');
}

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
  import type ${component.className} from '${component.importPath}';

  defineOptions({
    name: '${component.className}',
    inheritAttrs: false,
  });

  const model = defineModel<${component.modelType}>();
</script>

<template>
  <${component.tagName}
    v-bind="$attrs"
    .modelValue="model"
    @model-value-changed="model = ($event.target as ${component.className})?.modelValue"
  >
    <slot></slot>
  </${component.tagName}>
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
  import type ${component.className} from '${component.importPath}';

  defineOptions({
    name: '${component.className}',
    inheritAttrs: false,
  });

  const model = defineModel<boolean>();
</script>

<template>
  <${component.tagName}
    v-bind="$attrs"
    .checked="model"
    @model-value-changed="model = ($event.target as ${component.className})?.checked"
  >
    <slot></slot>
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
  import type ${component.className} from '${component.importPath}';

  defineOptions({
    name: '${component.className}',
    inheritAttrs: false,
  });

  const model = defineModel<${component.modelType}>();
</script>

<template>
  <${component.tagName}
    v-bind="$attrs"
    .modelValue="model"
    @model-value-changed="model = ($event.target as ${component.className})?.modelValue"
  >
    <slot></slot>
  </${component.tagName}>
</template>
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
    tagName: 'craft-drawer',
    className: 'CraftDrawer',
    importPath: '../components/drawer/drawer',
  },
  {
    tagName: 'craft-dropdown',
    className: 'CraftDropdown',
    importPath: '../components/dropdown/dropdown',
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
    tagName: 'c-tooltip',
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
    console.log(`  Generated: ${VUE_DIR}/${component.fileName}.vue`);
    count++;
  }

  // Generate checked-based wrappers
  for (const component of CHECKED_COMPONENTS) {
    const content = generateCheckedWrapper(component);
    const filePath = resolve(VUE_DIR, `${component.fileName}.vue`);
    writeFileSync(filePath, content);
    console.log(`  Generated: ${VUE_DIR}/${component.fileName}.vue`);
    count++;
  }

  // Generate group wrappers
  for (const component of GROUP_COMPONENTS) {
    const content = generateGroupWrapper(component);
    const filePath = resolve(VUE_DIR, `${component.fileName}.vue`);
    writeFileSync(filePath, content);
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
