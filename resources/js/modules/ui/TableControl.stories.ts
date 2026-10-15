import {
  createApp,
  defineComponent,
  h,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
  type App,
  type PropType,
} from 'vue';
import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {createCpComponentRegistry} from '@/bootstrap/components';
import UiRenderer from './UiRenderer.vue';
import {registerUiComponents} from './register';
import type {UiNodePayload, UiPayload, UiProperties} from './types';

/**
 * Editable tables (`craft:table`) that together have one column of every type
 * `CraftCms\Cms\Ui\Controls\TableColumn` supports, split into text, choice,
 * and other tables. The cell payloads mirror what the server resolves for each
 * type, so the story shows the same controls, padding, and alignment as the CP.
 *
 * Each row is a nested UI, which `UiRenderer` resolves through the CP's
 * component registry. The tables mount in their own Vue app so that registry
 * doesn't leak into other stories.
 *
 * `heading` and `html` columns render the row's value as-is rather than a
 * control, and the `hidden` column has no visible cells.
 */

type Mode = 'editable' | 'readOnly' | 'disabled';

const options = [
  {label: 'One', value: 'one'},
  {label: 'Two', value: 'two'},
];

const columns = {
  heading: {heading: 'Heading', type: 'heading', class: 'thin'},
  singleline: {heading: 'Single-line', type: 'singleline', placeholder: 'Text'},
  multiline: {heading: 'Multi-line', type: 'multiline'},
  email: {heading: 'Email', type: 'email'},
  url: {heading: 'URL', type: 'url'},
  number: {heading: 'Number', type: 'number', min: 0, step: 1},
  money: {heading: 'Money', type: 'money', currency: 'USD'},
  date: {heading: 'Date', type: 'date'},
  time: {heading: 'Time', type: 'time'},
  color: {heading: 'Color', type: 'color', class: 'thin'},
  icon: {heading: 'Icon', type: 'icon', class: 'thin'},
  checkbox: {heading: 'Checkbox', type: 'checkbox', class: 'thin centered'},
  lightswitch: {
    heading: 'Lightswitch',
    type: 'lightswitch',
    class: 'thin centered',
  },
  select: {heading: 'Select', type: 'select', class: 'thin', options},
  radio: {heading: 'Radio', type: 'radio', options},
  autosuggest: {
    heading: 'Autosuggest',
    type: 'autosuggest',
    options: [
      {label: 'Alpha', value: 'alpha'},
      {label: 'Beta', value: 'beta'},
    ],
  },
  template: {
    heading: 'Template',
    type: 'template',
    options: [
      {label: '_entry.twig', value: '_entry.twig'},
      {label: 'news/_entry.twig', value: 'news/_entry.twig'},
    ],
  },
  html: {heading: 'HTML', type: 'html'},
  hidden: {heading: 'Hidden', type: 'hidden'},
};

/**
 * The control each column type resolves to: `[class, component, props]`.
 * `heading` and `html` have no control.
 */
const cellControls: Array<[string, string, string, UiProperties]> = [
  ['singleline', 'Text', 'craft:text', {placeholder: 'Text'}],
  ['multiline', 'Textarea', 'craft:textarea', {rows: 1}],
  ['email', 'Text', 'craft:text', {inputType: 'email'}],
  ['url', 'Text', 'craft:text', {inputType: 'url'}],
  ['number', 'Number', 'craft:number', {inputType: 'number', min: 0, step: 1}],
  [
    'money',
    'Money',
    'craft:money',
    {currency: 'USD', locale: 'en-US', showCurrency: true},
  ],
  ['date', 'Date', 'craft:date', {inputType: 'date'}],
  ['time', 'Time', 'craft:time', {inputType: 'time'}],
  ['color', 'Color', 'craft:color', {presets: []}],
  ['icon', 'IconPicker', 'craft:icon-picker', {freeOnly: false}],
  ['checkbox', 'Checkbox', 'craft:checkbox', {}],
  ['lightswitch', 'Lightswitch', 'craft:lightswitch', {size: 'small'}],
  [
    'select',
    'Choice',
    'craft:choice',
    {options, multiple: false, presentation: 'select', placeholder: false},
  ],
  [
    'radio',
    'Choice',
    'craft:choice',
    {options, multiple: false, presentation: 'radios'},
  ],
  [
    'autosuggest',
    'Combobox',
    'craft:combobox',
    {options: columns.autosuggest.options, showAllOnEmpty: true},
  ],
  [
    'template',
    'Combobox',
    'craft:combobox',
    {options: columns.template.options, showAllOnEmpty: true},
  ],
  ['hidden', 'Hidden', 'craft:hidden', {}],
];

const rows = [
  {
    heading: 'First row',
    singleline: 'Hello',
    multiline: 'A longer\nvalue',
    email: 'hello@example.com',
    url: 'https://craftcms.com',
    number: 42,
    money: null,
    date: '2026-10-12',
    time: '09:30',
    color: '#e5422b',
    icon: null,
    checkbox: true,
    lightswitch: true,
    select: 'two',
    radio: 'one',
    autosuggest: 'alpha',
    template: '_entry.twig',
    html: '<em>Rendered</em> HTML',
    hidden: 'first',
  },
  {
    heading: 'Second row',
    singleline: null,
    multiline: null,
    email: null,
    url: null,
    number: null,
    money: null,
    date: null,
    time: null,
    color: null,
    icon: null,
    checkbox: false,
    lightswitch: false,
    select: 'one',
    radio: 'two',
    autosuggest: null,
    template: null,
    html: '<strong>Bold</strong>',
    hidden: 'second',
  },
];

type ColumnKey = keyof typeof columns;

/** The column types are split across a few tables to keep each one readable. */
const tables: Array<{path: string; label: string; columns: ColumnKey[]}> = [
  {
    path: 'text',
    label: 'Text columns',
    columns: [
      'heading',
      'singleline',
      'multiline',
      'email',
      'url',
      'number',
      'money',
      'date',
      'time',
    ],
  },
  {
    path: 'choices',
    label: 'Choice columns',
    columns: [
      'heading',
      'checkbox',
      'lightswitch',
      'select',
      'radio',
      'autosuggest',
      'template',
    ],
  },
  {
    path: 'other',
    label: 'Other columns',
    columns: ['heading', 'color', 'icon', 'html', 'hidden'],
  },
];

function pick<T extends object>(source: T, keys: ColumnKey[]): Partial<T> {
  return Object.fromEntries(
    Object.entries(source).filter(([key]) => keys.includes(key as ColumnKey))
  ) as Partial<T>;
}

function cells(
  keys: ColumnKey[],
  scope: string[],
  deltaGroup: string[],
  mode: Mode
): UiNodePayload[] {
  return cellControls
    .filter(([key]) => keys.includes(key as ColumnKey))
    .map(([key, type, component, props]) => ({
      type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
      component: 'craft:field',
      props: {
        label: columns[key as ColumnKey].heading,
        labelSrOnly: true,
      },
      control: {
        type: `CraftCms\\Cms\\Ui\\Controls\\${type}`,
        component,
        props,
        path: [...scope, key],
        mode,
        deltaGroup,
      },
    }));
}

function table(
  {path, label, columns: keys}: (typeof tables)[number],
  mode: Mode
): UiNodePayload {
  return {
    type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
    component: 'craft:field',
    props: {label},
    control: {
      type: 'CraftCms\\Cms\\Ui\\Controls\\Table',
      component: 'craft:table',
      nestsUis: true,
      path: [path],
      deltaGroup: [path],
      mode,
      props: {
        columns: pick(columns, keys),
        allowAdd: true,
        allowDelete: true,
        allowReorder: true,
        keyed: false,
        defaultValues: {},
        rowTemplate: {
          scope: [],
          refreshable: false,
          nodes: cells(keys, [], [path], mode),
        },
      },
      uis: rows.map((_, index) => ({
        scope: [path, String(index)],
        refreshable: false,
        nodes: cells(keys, [path, String(index)], [path], mode),
      })),
    },
  };
}

function payload(mode: Mode): UiPayload {
  return {
    scope: [],
    refreshable: false,
    globalErrors: [],
    errors: [],
    values: Object.fromEntries(
      tables.map(({path, columns: keys}) => [
        path,
        rows.map((row) => pick(row, keys)),
      ])
    ),
    nodes: tables.map((config) => table(config, mode)),
  };
}

const TableStory = defineComponent({
  name: 'TableStory',
  props: {
    mode: {type: String as PropType<Mode>, default: 'editable'},
  },
  setup(props) {
    const host = ref<HTMLElement | null>(null);
    let app: App | null = null;

    function mount(): void {
      app?.unmount();
      if (!host.value) {
        return;
      }
      const mode = props.mode;
      app = createApp({render: () => h(UiRenderer, {payload: payload(mode)})});
      const registry = createCpComponentRegistry();
      registerUiComponents(registry);
      registry.install(app);
      app.mount(host.value);
    }

    onMounted(mount);
    watch(() => props.mode, mount);
    onBeforeUnmount(() => {
      app?.unmount();
      app = null;
    });

    // Sit on the CP's raised panel surface (the canvas is sunken, which hides
    // the sunken table headers), space the tables apart, and let any that's
    // wider than the canvas scroll.
    return () =>
      h('div', {
        ref: host,
        style: {
          display: 'flex',
          flexDirection: 'column',
          gap: 'var(--c-spacing-xl)',
          padding: 'var(--c-spacing-lg)',
          borderRadius: 'var(--c-radius-lg)',
          backgroundColor: 'var(--c-surface-raised)',
          overflowX: 'auto',
        },
      });
  },
});

const meta = {
  title: 'Ui/TableControl',
  component: TableStory,
  parameters: {
    layout: 'padded',
    docs: {
      description: {
        component:
          'The editable table behind `craft:table`, split into text, choice, ' +
          'and other tables that together have a column for every ' +
          '`TableColumn` type: heading, singleline, multiline, email, url, ' +
          'number, money, date, time, color, icon, checkbox, lightswitch, ' +
          'select, radio, autosuggest, template, html, and hidden. Text and ' +
          'select cells fill their cell; everything else gets `th` padding. ' +
          '`thin` columns shrink to their content and `centered` ones center ' +
          'it.',
      },
    },
  },
  argTypes: {
    mode: {
      control: 'inline-radio',
      options: ['editable', 'readOnly', 'disabled'],
    },
  },
  args: {
    mode: 'editable',
  },
} satisfies Meta<typeof TableStory>;

export default meta;

type Story = StoryObj<typeof meta>;

export const AllColumnTypes: Story = {};

export const ReadOnly: Story = {
  args: {mode: 'readOnly'},
};

export const Disabled: Story = {
  args: {mode: 'disabled'},
};
