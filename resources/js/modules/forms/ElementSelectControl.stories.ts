import type {Meta, StoryObj} from '@storybook/vue3-vite';
import ElementSelectControl from './ElementSelectControl.vue';
import type {FormControlPayload} from './types';

/**
 * `<craft-element-select-input>` stays inert in Storybook, which is what makes
 * these stories work without the legacy runtime: `ControllerElement` only boots
 * its garnish controller once `:scope > .elements` resolves, and this control
 * renders chips inside a `<craft-pane>` instead. The element is a passive
 * wrapper here, so everything you can see is Vue's.
 *
 * The consequence for UI work: anything that reaches *through* the custom
 * element into the controller — the modal behind "Add", Replace, Remove — is a
 * no-op in Storybook. Layout, chips, menus and empty states are all live.
 */

type Props = Record<string, unknown>;

function control(props: Props = {}): FormControlPayload<any> {
  return {
    type: 'CraftCms\\Cms\\Form\\Controls\\ElementSelect',
    component: 'craft:element-select',
    props: {
      elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
      customElement: 'craft-element-select-input',
      elements: [],
      sources: null,
      criteria: {},
      selectionLabel: 'Add an entry',
      limit: null,
      showSiteMenu: false,
      ...props,
    },
    path: ['related'],
    mode: 'editable',
    deltaGroup: ['related'],
  } as FormControlPayload<any>;
}

/** `elements` and `value` have to agree — the chips render from the pair. */
function withElements(
  labels: string[],
  props: Props = {}
): {control: FormControlPayload<any>; value: number[]} {
  const elements = labels.map((label, index) => ({id: index + 1, label}));

  return {
    control: control({elements, ...props}),
    value: elements.map((element) => element.id),
  };
}

const ENTRIES = [
  'Welcome to Craft 6',
  'Porting the element index to Vue',
  'Inertia in the Control Panel',
];

const ASSET_PROPS: Props = {
  elementType: 'CraftCms\\Cms\\Asset\\Elements\\Asset',
  customElement: 'craft-asset-select-input',
  selectionLabel: 'Add an asset',
};

/**
 * Upload wiring reaches for `Craft.createUploader` and jQuery on mount, neither
 * of which exists here. Stubbing them keeps the upload stories rendering; the
 * button is real, the uploader behind it is not.
 */
function stubCraftUploader(): void {
  const scope = window as unknown as Record<string, unknown>;

  scope.$ ??= (element: unknown) => element;
  scope.Craft = {
    ...(scope.Craft as object),
    createUploader: () => ({
      destroy() {},
      isLastUpload: () => true,
      setParams() {},
    }),
  };
}

const meta = {
  title: 'Forms/ElementSelectControl',
  component: ElementSelectControl,
  parameters: {
    docs: {
      description: {
        component:
          'The Vue control behind `craft:element-select` — the field that ' +
          'relates elements. Renders the selected elements as chips inside a ' +
          '`<craft-pane>`, with the selection button (and, for asset fields ' +
          'that allow it, an upload button) in the pane header.',
      },
    },
  },
  argTypes: {
    editable: {
      control: 'boolean',
      description: 'Read-only mode drops the header and the chip menus.',
    },
  },
} satisfies Meta<typeof ElementSelectControl>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Empty: Story = {
  args: {control: control(), value: [], editable: true},
};

export const WithSelection: Story = {
  args: {...withElements(ENTRIES), editable: true},
};

/** A single relation — what `maxRelations: 1` fields look like once filled. */
export const SingleRelation: Story = {
  args: {...withElements(ENTRIES.slice(0, 1), {limit: 1}), editable: true},
};

/**
 * Read-only. The header, the hidden inputs and the chip action menus all drop
 * out, so the chips are purely presentational.
 */
export const ReadOnly: Story = {
  args: {...withElements(ENTRIES), editable: false},
};

/** Long labels are the usual layout hazard — chips have to wrap or truncate. */
export const LongLabels: Story = {
  args: {
    ...withElements([
      'A quite unreasonably long entry title that will not fit on one line in a narrow field',
      'Short',
      'Another exceedingly verbose title, written by someone paid per character',
    ]),
    editable: true,
  },
};

/** Enough chips to show how the list behaves once it stops being small. */
export const ManySelected: Story = {
  args: {
    ...withElements(
      Array.from({length: 12}, (_, index) => `Related entry ${index + 1}`)
    ),
    editable: true,
  },
};

/** An asset field with uploads switched off — selection only. */
export const AssetsWithoutUpload: Story = {
  args: {
    ...withElements(['seascape.jpg', 'brochure.pdf'], ASSET_PROPS),
    editable: true,
  },
};

/**
 * An asset field that can upload: `AssetSelect` sends `canUpload`,
 * `uploadFolderId` and `fsType`, and the header gains the upload button.
 */
export const AssetsWithUpload: Story = {
  decorators: [
    () => {
      stubCraftUploader();

      return {template: '<story />'};
    },
  ],
  args: {
    ...withElements(['seascape.jpg', 'brochure.pdf'], {
      ...ASSET_PROPS,
      canUpload: true,
      uploadFolderId: 1,
      fsType: 'CraftCms\\Cms\\Filesystem\\Filesystems\\Local',
    }),
    editable: true,
  },
};

/** The same field with nothing chosen yet — the emptiest upload target. */
export const AssetsWithUploadEmpty: Story = {
  decorators: [
    () => {
      stubCraftUploader();

      return {template: '<story />'};
    },
  ],
  args: {
    control: control({
      ...ASSET_PROPS,
      canUpload: true,
      uploadFolderId: 1,
      fsType: 'CraftCms\\Cms\\Filesystem\\Filesystems\\Local',
    }),
    value: [],
    editable: true,
  },
};
