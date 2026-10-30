<script setup lang="ts">
  /**
   * The control panel's keyboard shortcuts, for the palette's “Keyboard
   * shortcuts” item. Each key is shown as its symbol on the platform it's
   * pressed on, and named for screen readers.
   */
  import {t} from '@craftcms/ui';

  defineEmits<{
    (event: 'back'): void;
  }>();

  interface Key {
    symbol: string;
    name: string;
  }

  const isMac = /Mac|iPhone|iPad/.test(navigator.platform);

  const modifier: Key = isMac
    ? {symbol: '⌘', name: t('Command')}
    : {symbol: 'Ctrl', name: t('Control')};

  const shortcuts: Array<{keys: Key[]; description: string}> = [
    {
      keys: [modifier, {symbol: 'K', name: 'K'}],
      description: t('Open the command palette'),
    },
    {
      keys: [{symbol: '/', name: t('Slash')}],
      description: t('Open the command palette when not typing in a field'),
    },
    {
      keys: [modifier, {symbol: 'S', name: 'S'}],
      description: t('Save'),
    },
    {
      keys: [
        modifier,
        isMac
          ? {symbol: '↩', name: t('Return')}
          : {symbol: 'Enter', name: t('Enter')},
      ],
      description: t('Submit the form a text field is in'),
    },
    {
      keys: [{symbol: 'Esc', name: t('Escape')}],
      description: t('Close the current modal or slideout'),
    },
  ];
</script>

<template>
  <table class="cp-table cp-table--auto command-palette-shortcuts">
    <caption class="sr-only">
      {{
        t('Keyboard shortcuts')
      }}
    </caption>
    <thead>
      <tr>
        <th scope="col">{{ t('Shortcut') }}</th>
        <th scope="col">{{ t('Action') }}</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="shortcut in shortcuts" :key="shortcut.description">
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
    font-weight: inherit;
  }

  .command-palette-shortcuts__keys {
    display: flex;
    align-items: baseline;
    gap: var(--c-spacing-xs);
  }
</style>
