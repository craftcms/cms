import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {computed, defineComponent, type PropType, ref} from 'vue';
import {useSelectable} from './useSelectable';

interface Item {
  id: number;
  label: string;
}

const items: Item[] = [
  {id: 1, label: 'Homepage'},
  {id: 2, label: 'About us'},
  {id: 3, label: 'Pricing'},
  {id: 4, label: 'Careers'},
  {id: 5, label: 'Contact'},
];

/**
 * A minimal list wired to `useSelectable` — enough to exercise the gestures
 * without dragging in a real element index. Every story below renders this same
 * component; only the composable's options differ.
 */
const SelectableList = defineComponent({
  props: {
    click: {type: String as PropType<'replace' | 'toggle'>, default: 'toggle'},
    readOnly: {type: Boolean, default: false},
    enabled: {type: Boolean, default: true},
    // Stands in for a row the caller won't let the user select.
    lockPricing: {type: Boolean, default: false},
    withCheckbox: {type: Boolean, default: false},
  },
  setup(props) {
    const rows = ref<Item[]>([...items]);
    const ids = computed(() => rows.value.map((row) => row.id));

    const selection = useSelectable<number>({
      ids,
      enabled: () => props.enabled,
      readOnly: () => props.readOnly,
      click: () => props.click,
      canSelect: (id) => !props.lockPricing || id !== 3,
    });

    function remove(): void {
      const remaining = ids.value.filter((id) => !selection.isSelected(id));

      // Prune against the list that is about to land, not the one still shown.
      selection.prune(remaining);
      rows.value = rows.value.filter((row) => remaining.includes(row.id));
    }

    // `model-value-changed` carries no modifier keys, so a shift-range has to be
    // read off the native click that preceded it.
    const pendingShiftKey = ref(false);

    function rememberShift(event: MouseEvent): void {
      pendingShiftKey.value = event.shiftKey;
    }

    function onChecked(id: number, event: Event): void {
      selection.setChecked(id, (event.target as HTMLInputElement).checked, {
        shiftKey: pendingShiftKey.value,
      });
    }

    return {rows, selection, remove, rememberShift, onChecked};
  },
  template: `
    <div class="flex flex-col gap-3">
      <ul class="flex flex-col gap-1">
        <li
          v-for="row in rows"
          :key="row.id"
          class="flex items-center justify-between gap-3 rounded border px-3 py-2 cursor-default select-none"
          :class="selection.isSelected(row.id)
            ? 'border-blue-500 bg-blue-50'
            : 'border-gray-200 bg-white'"
          @click="(event) => selection.handleClick(row.id, event)"
        >
          <span class="flex items-center gap-2">
            <input
              v-if="withCheckbox"
              type="checkbox"
              :checked="selection.isSelected(row.id)"
              @click="rememberShift"
              @change="(event) => onChecked(row.id, event)"
            />
            {{ row.label }}
          </span>
          <a href="#" class="text-xs underline" @click.prevent>An inner link</a>
        </li>
      </ul>

      <div class="flex items-center gap-2 text-sm">
        <button type="button" class="rounded border px-2 py-1" @click="selection.selectAll(true)">
          Select all
        </button>
        <button type="button" class="rounded border px-2 py-1" @click="selection.clear()">
          Clear
        </button>
        <button
          type="button"
          class="rounded border px-2 py-1"
          :disabled="!selection.hasSelection.value"
          @click="remove"
        >
          Remove selected
        </button>
      </div>

      <p class="text-xs text-gray-600">
        selected: [{{ selection.selectedIds.value.join(', ') }}] · anchor:
        {{ selection.anchorIndex.value ?? '—' }}
      </p>
    </div>
  `,
});

const meta = {
  title: 'Composables/useSelectable',
  component: SelectableList,
  parameters: {
    docs: {
      description: {
        component:
          'Anchor-based list selection — plain click, ctrl/cmd-click, and ' +
          'shift-click ranges — for any ordered list of ids. The composable ' +
          'owns the selection *algorithm*; where the selection is stored is ' +
          'pluggable, so a caller that already has somewhere authoritative to ' +
          'keep it (a TanStack table, say) can hand that over instead of ' +
          'mirroring state. Click a row, then shift-click another to select ' +
          'the range; the inner link is ignored on purpose.',
      },
    },
  },
} satisfies Meta<typeof SelectableList>;

export default meta;

type Story = StoryObj<typeof meta>;

/**
 * `click: 'toggle'` (the default) flips just the clicked row and leaves the
 * rest alone — what a checkbox-driven list wants, and what the element index
 * uses.
 */
export const Toggle: Story = {
  args: {click: 'toggle'},
};

/**
 * `click: 'replace'` collapses the selection to the clicked row, the way a file
 * list behaves. Ctrl/cmd-click still adds, and shift-click still ranges. This is
 * what `ElementSelectControl`'s chips use.
 */
export const Replace: Story = {
  args: {click: 'replace'},
};

/**
 * `readOnly` freezes the selection: reads still work, every write is ignored.
 * Clicking, select-all and range selection all no-op.
 */
export const ReadOnly: Story = {
  args: {readOnly: true},
};

/**
 * `enabled: false` turns the click gesture off entirely, while leaving the
 * imperative API usable — for a list that is only selectable in some modes.
 */
export const NotSelectable: Story = {
  args: {enabled: false},
};

/**
 * A checkbox per row, driven through `setChecked`. Clicking the box doesn't also
 * select the row — `handleClick` sees a focusable control in the event's path and
 * steps aside — and shift-clicking it still ranges from the anchor.
 *
 * The real control panel uses `craft-checkbox` here, which additionally re-fires
 * its change event on programmatic updates; `setChecked` ignores those no-ops.
 */
export const WithCheckboxes: Story = {
  args: {withCheckbox: true},
};

/**
 * `canSelect` vetoes individual items. "Pricing" can't be selected here, whether
 * it's clicked directly, swept up in a shift-range, or caught by select-all.
 */
export const WithVetoedItem: Story = {
  args: {lockPricing: true},
};
