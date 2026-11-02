<script setup lang="ts">
  /**
   * The control panel's keyboard shortcuts, for the palette's “Keyboard
   * shortcuts” item. Each key is shown as its symbol on the platform it's
   * pressed on, and named for screen readers.
   */
  import {t} from '@craftcms/ui';
  import {usePage} from '@inertiajs/vue3';
  import {useId} from 'vue';
  import {S_KEY} from '@craftcms/garnish';
  import {uiLayerManager} from '@/common/slideouts/panel-stack';
  import {availableShortcuts, type ContextualShortcut} from '../registry';

  defineEmits<{
    (event: 'back'): void;
  }>();

  interface Key {
    symbol: string;
    name: string;
  }

  interface Shortcut {
    keys: Key[];
    description: string;
    /** Listed only where this works. Always listed without one. */
    when?: ContextualShortcut;
  }

  const isMac = /Mac|iPhone|iPad/.test(navigator.platform);

  const modifier: Key = isMac
    ? {symbol: '⌘', name: t('Command')}
    : {symbol: 'Ctrl', name: t('Control')};

  /**
   * Command/Ctrl with a key, and any other modifiers in the platform's usual
   * order: ⌥⇧⌘ on a Mac, Ctrl+Alt+Shift elsewhere.
   */
  function combo(key: Key, {alt = false, shift = false} = {}): Key[] {
    const altKey: Key = isMac
      ? {symbol: '⌥', name: t('Option')}
      : {symbol: 'Alt', name: t('Alt')};
    const shiftKey: Key = isMac
      ? {symbol: '⇧', name: t('Shift')}
      : {symbol: 'Shift', name: t('Shift')};
    const others = [...(alt ? [altKey] : []), ...(shift ? [shiftKey] : [])];

    return isMac ? [...others, modifier, key] : [modifier, ...others, key];
  }

  /** A key named for screen readers by its own label, such as a letter. */
  function letter(symbol: string): Key {
    return {symbol, name: symbol};
  }

  /**
   * The save shortcuts registered with Garnish's layers — by legacy pages,
   * element editors and slideouts — that a press would reach now: the top
   * layer's, and those of the layers below it that it lets shortcuts through to.
   */
  function garnishShortcuts(): ContextualShortcut[] {
    const manager = uiLayerManager();
    const found: ContextualShortcut[] = [];

    for (let index = manager.layer; index >= 0; index--) {
      const layer = manager.layers[index]!;

      for (const {shortcut} of layer.shortcuts) {
        if (shortcut.keyCode !== S_KEY || !shortcut.ctrl) {
          continue;
        }

        if (shortcut.alt && !shortcut.shift) {
          found.push('applyDraft');
        } else if (shortcut.shift && !shortcut.alt) {
          found.push('saveAndAddAnother');
        } else if (!shortcut.shift && !shortcut.alt) {
          found.push('save');
        }
      }

      if (!layer.options.bubble) {
        break;
      }
    }

    return found;
  }

  const available = new Set([...availableShortcuts(), ...garnishShortcuts()]);

  /** The shortcuts that work everywhere come first, without a heading. */
  const allGroups: Array<{heading?: string; shortcuts: Shortcut[]}> = [
    {
      shortcuts: [
        {
          keys: combo(letter('K')),
          description: t('Open the command palette'),
        },
        {
          keys: [{symbol: '/', name: t('Slash')}],
          description: t('Open the command palette when not typing in a field'),
        },
        // Only for users the AI assistant is available to.
        ...(usePage<{craft?: {currentUser?: {aiAssistant?: boolean} | null}}>()
          .props.craft?.currentUser?.aiAssistant
          ? [
              {
                keys: combo(letter('I')),
                description: t('Ask AI'),
              },
            ]
          : []),
        {
          keys: [{symbol: 'Esc', name: t('Escape')}],
          description: t('Close the current modal or slideout'),
        },
      ],
    },
    {
      heading: t('Forms'),
      shortcuts: [
        {
          keys: combo(letter('S')),
          description: t('Save'),
          when: 'save',
        },
        {
          keys: combo(letter('S'), {shift: true}),
          description: t('Save and add another'),
          when: 'saveAndAddAnother',
        },
        {
          keys: combo(letter('S'), {alt: true}),
          description: t('Apply draft'),
          when: 'applyDraft',
        },
        {
          keys: combo(
            isMac
              ? {symbol: '↩', name: t('Return')}
              : {symbol: 'Enter', name: t('Enter')}
          ),
          description: t('Submit the form a text field is in'),
        },
      ],
    },
    {
      heading: t('Markdown fields'),
      shortcuts: [
        {keys: combo(letter('B')), description: t('Bold')},
        {keys: combo(letter('I')), description: t('Italic')},
        {keys: combo(letter('E')), description: t('Code')},
        {keys: combo(letter('K')), description: t('Insert a link')},
        {
          keys: combo({symbol: ']', name: t('Right bracket')}),
          description: t('Indent'),
        },
        {
          keys: combo({symbol: '[', name: t('Left bracket')}),
          description: t('Outdent'),
        },
        {
          keys: combo(letter('P'), {shift: true}),
          description: t('Show or hide the preview'),
        },
      ].map((shortcut) => ({...shortcut, when: 'markdown' as const})),
    },
    {
      heading: t('Image editor'),
      shortcuts: [
        {keys: combo(letter('Z')), description: t('Undo')},
        {keys: combo(letter('Z'), {shift: true}), description: t('Redo')},
      ].map((shortcut) => ({...shortcut, when: 'imageEditor' as const})),
    },
  ];

  const groups = allGroups
    .map((group) => ({
      ...group,
      shortcuts: group.shortcuts.filter(
        ({when}) => when === undefined || available.has(when)
      ),
    }))
    .filter((group) => group.shortcuts.length > 0);

  const id = useId();
</script>

<template>
  <div class="command-palette-shortcuts">
    <section
      v-for="(group, groupIndex) in groups"
      :key="group.heading ?? ''"
      class="command-palette-shortcuts__group"
    >
      <h3
        v-if="group.heading"
        :id="`${id}-${groupIndex}`"
        class="command-palette-shortcuts__heading"
      >
        {{ group.heading }}
      </h3>
      <table
        class="cp-table cp-table--auto"
        :aria-labelledby="group.heading ? `${id}-${groupIndex}` : undefined"
      >
        <thead :class="{'sr-only': groupIndex > 0}">
          <tr>
            <th scope="col">{{ t('Shortcut') }}</th>
            <th scope="col">{{ t('Action') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="shortcut in group.shortcuts" :key="shortcut.description">
            <th scope="row">
              <span class="command-palette-shortcuts__keys">
                <template v-for="(key, index) in shortcut.keys" :key="index">
                  <kbd>
                    <span aria-hidden="true">{{ key.symbol }}</span>
                    <span class="sr-only">{{ key.name }}</span>
                  </kbd>
                  <span v-if="index < shortcut.keys.length - 1" class="sr-only"
                    >+</span
                  >
                </template>
              </span>
            </th>
            <td class="wrap">{{ shortcut.description }}</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>

<style scoped>
  /* Keeps the keys in line with the view's heading above. */
  .command-palette-shortcuts :is(th, td):first-child {
    padding-inline-start: 0;
  }

  .command-palette-shortcuts :is(th, td):last-child {
    padding-inline-end: 0;
    inline-size: 100%;
  }

  /* Sits the column labels as far below the view's heading as they are from its sides. */
  .command-palette-shortcuts thead th {
    height: auto;
    padding-block-start: 0;
  }

  /* The keys are row headers for the table's sake, not to be bolded. */
  .command-palette-shortcuts tbody th {
    min-inline-size: 10rem;
    font-weight: inherit;
  }

  .command-palette-shortcuts__group + .command-palette-shortcuts__group {
    margin-block-start: var(--c-spacing-lg);
  }

  .command-palette-shortcuts__heading {
    margin: 0;
    padding-block-end: var(--c-spacing-xs);
    font-size: var(--c-text-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--c-text-quiet);
  }

  .command-palette-shortcuts__keys {
    display: flex;
    align-items: baseline;
    gap: var(--c-spacing-xs);
  }
</style>
