<script setup lang="ts">
  /**
   * The control panel's keyboard shortcuts, for the palette's “Keyboard
   * shortcuts” item: the ones registered with `registerShortcut()` that work
   * where the palette was opened, and the described shortcuts of Garnish's
   * layers.
   * Each key is shown as its symbol on the platform it's pressed on, and named
   * for screen readers.
   */
  import {t} from '@craftcms/ui';
  import {detectPlatform, type ParsedHotkeyChord} from '@tanstack/hotkeys';
  import {useId} from 'vue';
  import {uiLayerManager} from '@/common/slideouts/panel-stack';
  import {listedShortcuts, type ListedShortcut} from '@/common/shortcuts';

  defineEmits<{
    (event: 'back'): void;
  }>();

  interface Key {
    symbol: string;
    name: string;
  }

  const isMac = detectPlatform() === 'mac';

  /** The keys of a chord, modifiers first, in the platform's usual order. */
  function keysOf(chord: ParsedHotkeyChord): Key[] {
    const modifiers: Key[] = isMac
      ? [
          ...(chord.ctrl ? [{symbol: '⌃', name: t('Control')}] : []),
          ...(chord.alt ? [{symbol: '⌥', name: t('Option')}] : []),
          ...(chord.shift ? [{symbol: '⇧', name: t('Shift')}] : []),
          ...(chord.meta ? [{symbol: '⌘', name: t('Command')}] : []),
        ]
      : [
          ...(chord.ctrl ? [{symbol: 'Ctrl', name: t('Control')}] : []),
          ...(chord.alt ? [{symbol: 'Alt', name: t('Alt')}] : []),
          ...(chord.shift ? [{symbol: 'Shift', name: t('Shift')}] : []),
          ...(chord.meta ? [{symbol: 'Meta', name: t('Meta')}] : []),
        ];

    return [...modifiers, keyOf(chord.key ?? chord.code ?? '')];
  }

  /** Key names for Garnish's key codes that aren't a character's. */
  const keyCodeKeys: Record<number, string> = {
    13: 'Enter',
    27: 'Escape',
    191: '/',
    190: '.',
    219: '[',
    221: ']',
  };

  function keyOf(key: string): Key {
    switch (key) {
      case 'Enter':
        return isMac
          ? {symbol: '↩', name: t('Return')}
          : {symbol: 'Enter', name: t('Enter')};
      case 'Escape':
        return {symbol: 'Esc', name: t('Escape')};
      case '/':
        return {symbol: '/', name: t('Slash')};
      case '.':
        return {symbol: '.', name: t('Period')};
      case '[':
        return {symbol: '[', name: t('Left bracket')};
      case ']':
        return {symbol: ']', name: t('Right bracket')};
      default:
        return {symbol: key, name: key};
    }
  }

  /**
   * The described shortcuts registered with Garnish's layers — by legacy
   * pages, element editors and slideouts — that a press would reach now: the
   * top layer's, and those of the layers below it that it lets shortcuts
   * through to.
   */
  function garnishShortcuts(): ListedShortcut[] {
    const manager = uiLayerManager();
    const found: ListedShortcut[] = [];

    for (let index = manager.layer; index >= 0; index--) {
      const layer = manager.layers[index]!;

      for (const {shortcut, description, group} of layer.shortcuts) {
        if (description) {
          found.push({
            keys: {
              key:
                keyCodeKeys[shortcut.keyCode] ??
                String.fromCharCode(shortcut.keyCode),
              ctrl: shortcut.ctrl && !isMac,
              meta: shortcut.ctrl && isMac,
              alt: shortcut.alt,
              shift: shortcut.shift,
              modifiers: [],
            },
            description,
            group,
          });
        }
      }

      if (!layer.options.bubble) {
        break;
      }
    }

    return found;
  }

  const escape: ListedShortcut = {
    keys: {
      key: 'Escape',
      ctrl: false,
      meta: false,
      alt: false,
      shift: false,
      modifiers: [],
    },
    description: t('Close the current modal or slideout'),
  };

  /** Shortcuts without a group lead the list; groups follow in the order they're first seen. */
  const groups: Array<{
    heading?: string;
    shortcuts: Array<{id: string; keys: Key[]; description: string}>;
  }> = [];

  for (const shortcut of [
    ...listedShortcuts(),
    escape,
    ...garnishShortcuts(),
  ]) {
    const keys = keysOf(shortcut.keys);
    const id = `${keys.map(({symbol}) => symbol).join('+')}|${shortcut.description}`;
    let group = groups.find(({heading}) => heading === shortcut.group);

    if (!group) {
      group = {heading: shortcut.group, shortcuts: []};

      if (shortcut.group) {
        groups.push(group);
      } else {
        groups.unshift(group);
      }
    }

    if (!group.shortcuts.some((existing) => existing.id === id)) {
      group.shortcuts.push({id, keys, description: shortcut.description});
    }
  }

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
          <tr v-for="shortcut in group.shortcuts" :key="shortcut.id">
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
