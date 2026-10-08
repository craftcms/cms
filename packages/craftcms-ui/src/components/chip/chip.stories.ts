import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html, nothing} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import {Color} from '../../constants/colors';

import './chip.js';
import '../status/status.js';
import '../button/button.js';
import '../icon/icon.js';
import '../action-menu/action-menu.js';
import '../avatar/avatar.js';
import '../badge/badge.js';
import '../info-icon/info-icon.js';
import '../reorder-button/reorder-button.js';
import '../truncate/truncate.js';
import type CraftChip from './chip.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `chip.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} = getStorybookHelpers<CraftChip>('craft-chip');

const ACTION_BUTTON = `<craft-button icon size="small" variant="plain">
  <craft-icon name="ellipsis" label="Actions"></craft-icon>
</craft-button>`;

const meta = {
  title: 'Components/Chip',
  component: 'craft-chip',
  args: {...args, 'default-slot': 'Homepage'},
  argTypes,
  // Render from args alone so every control — attributes and slots — drives
  // the story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

/** Every `size` value, for the size stories below. */
const chipSizes = [
  {size: 'auto', label: 'Auto'},
  {size: 'small', label: 'Small'},
  {size: 'medium', label: 'Medium'},
  {size: 'large', label: 'Large'},
] as const;

/**
 * A chip with nothing but a label renders neither the prefix nor the suffix
 * region.
 */
export const Default: Story = {};

/**
 * The two regions are independent. Fill `suffix` for a per-chip action, and
 * `prefix` to supply your own leading content.
 */
export const PrefixAndSuffix: Story = {
  args: {
    'status-slot': '<craft-status status="live"></craft-status>',
    'suffix-slot': ACTION_BUTTON,
  },
};

export const CustomPrefix: Story = {
  args: {
    'prefix-slot': `<div style="padding: var(--_chip-spacing);">
  <craft-button size="small" inherit variant="primary" type="button">Btn</craft-button>
</div>`,
  },
};

export const SuffixOnly: Story = {
  args: {'suffix-slot': ACTION_BUTTON},
};

/**
 * `selectable` adds the selection checkbox; `selected` styles the chip to match
 * a selected thumbnail tile in the element index, so a selection reads the same
 * whichever view mode the elements are shown in.
 */
export const Selectable: Story = {
  args: {},
  render: () => html`
    <div
      style="display: flex; flex-direction: column; gap: 0.5rem; align-items: start;"
    >
      <craft-chip selectable select-label="Select Homepage"
        >Homepage</craft-chip
      >
      <craft-chip selectable selected select-label="Select About us"
        >About us</craft-chip
      >
    </div>
  `,
};

/**
 * Attaching an action menu after the chip has rendered, then adding items to
 * it. "Attach action menu" appends a `craft-action-menu` to the chip's
 * `suffix` slot, and the chip renders the suffix region without being told to
 * re-render. "Add action" appends an item to the menu that is already there.
 */
export const DeferredActions: Story = {
  parameters: {
    controls: {disable: true},
    docs: {
      // The feature here is imperative, so the rendered markup does not show
      // it. Keep this in sync with the handlers below.
      source: {
        code: `const chip = document.querySelector('craft-chip');

// Attach the menu once the entity's available actions are known.
const menu = document.createElement('craft-action-menu');
menu.slot = 'suffix';
menu.label = 'Actions';
menu.icon = 'ellipsis';
menu.actions = [
  {label: 'View', icon: 'eye', onClick: () => {}},
  {label: 'Edit', icon: 'pen', onClick: () => {}},
  {label: 'Delete', icon: 'trash', variant: 'danger', onClick: () => {}},
];

// The chip observes its own light DOM, so the suffix region appears on its own.
chip.append(menu);

// \`actions\` is a reactive property. Add an item by reassigning the array —
// pushing onto it in place does not trigger a re-render.
menu.actions = [
  ...menu.actions,
  {label: 'Custom action 1', icon: 'lightbulb', onClick: () => {}},
];`,
        language: 'js',
      },
    },
  },
  render: () => {
    let added = 0;

    const menuFor = (trigger: HTMLElement) =>
      trigger.parentElement?.querySelector('craft-action-menu') ?? null;

    const attachMenu = (event: Event) => {
      const trigger = event.currentTarget as HTMLElement;
      const chip = trigger.parentElement?.querySelector('craft-chip');
      if (!chip || chip.querySelector('[slot="suffix"]')) {
        return;
      }

      const menu = document.createElement('craft-action-menu');
      menu.slot = 'suffix';
      menu.label = 'Actions';
      menu.icon = 'ellipsis';
      menu.actions = [
        {label: 'View', icon: 'eye', onClick: () => {}},
        {label: 'Edit', icon: 'pen', onClick: () => {}},
        {label: 'Delete', icon: 'trash', variant: 'danger', onClick: () => {}},
      ];

      chip.append(menu);
    };

    const addAction = (event: Event) => {
      const menu = menuFor(event.currentTarget as HTMLElement);
      if (!menu) {
        return;
      }

      const current = Array.isArray(menu.actions) ? menu.actions : [];
      added++;

      // Reassign rather than push: `actions` is a reactive property, and Lit
      // compares by reference.
      menu.actions = [
        ...current,
        {
          label: `Custom action ${added}`,
          icon: 'lightbulb',
          onClick: () => {},
        },
      ];
    };

    return html`
      <div style="display: flex; gap: 1rem; align-items: center">
        <craft-chip>Homepage</craft-chip>
        <craft-button size="small" @click="${attachMenu}">
          Attach action menu
        </craft-button>
        <craft-button size="small" @click="${addAction}">
          Add action
        </craft-button>
      </div>
    `;
  },
};

/**
 * `show-thumb` is required. Without it, the `thumbnail` slot is not rendered,
 * and its content does not appear.
 *
 * The arg is the `showThumb` property rather than the `show-thumb` attribute:
 * the helpers' args carry both, and the property's `false` default is applied
 * after the attribute, which would switch the thumbnail back off.
 */
export const Thumbnail: Story = {
  args: {
    showThumb: true,
    'thumbnail-slot': '<img src="https://picsum.photos/120/120" alt="" />',
    'suffix-slot': ACTION_BUTTON,
  },
};

/**
 * The `icon` attribute is a shorthand for the `icon` slot, and setting it is
 * what causes that slot to be rendered.
 */
export const Icon: Story = {
  args: {icon: 'star'},
};

/**
 * The four `size` values on a bare chip. `small`, the default, is as tall as
 * a small suffix button so adding one doesn't change its height; `auto` has
 * the same padding without that minimum. `medium` and `large` add more
 * padding and a larger thumbnail.
 */
export const Sizes: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div
      style="display: flex; flex-direction: column; gap: 0.75rem; align-items: start"
    >
      ${chipSizes.map(
        ({size, label}) =>
          html`<craft-chip size="${size || nothing}">${label}</craft-chip>`
      )}
    </div>
  `,
};

/**
 * The same sizes with a thumbnail and a suffix, where the thumbnail's size
 * steps up with the chip's.
 */
export const SizesWithContent: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div
      style="display: flex; flex-direction: column; gap: 0.75rem; align-items: start"
    >
      ${chipSizes.map(
        ({size, label}) => html`
          <craft-chip size="${size || nothing}" show-thumb>
            <img slot="thumbnail" src="https://picsum.photos/120/120" alt="" />
            ${label}
            <craft-button icon size="small" variant="plain" slot="suffix">
              <craft-icon name="ellipsis" label="Actions"></craft-icon>
            </craft-button>
          </craft-chip>
        `
      )}
    </div>
  `,
};

/**
 * `align-items` sets where the prefix and suffix sit against the label. The default
 * `center` suits a single line; `start` and `end` keep them against the first
 * or last line of a taller label.
 */
export const Alignment: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: start">
      ${(['start', 'center', 'end'] as const).map(
        (align) => html`
          <craft-chip align-items="${align}" icon="newspaper">
            <div>
              <strong>${align}</strong>
              <div>Second line</div>
              <div>Third line</div>
            </div>
            <craft-button icon size="small" variant="plain" slot="suffix">
              <craft-icon name="ellipsis" label="Actions"></craft-icon>
            </craft-button>
          </craft-chip>
        `
      )}
    </div>
  `,
};

/**
 * `variant` sets the color group and `appearance` determines how those tokens
 * are applied. See [Variants & Appearances](?path=/docs/tokens-variants-appearances--docs)
 * for the underlying token mapping.
 */
export const Appearances: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div
      style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center"
    >
      ${['solid', 'fill', 'outline-fill', 'outline', 'plain'].map(
        (appearance) =>
          html`<craft-chip variant="info" appearance="${appearance}">
            ${appearance}
          </craft-chip>`
      )}
    </div>
  `,
};

/**
 * A chip inherits the palette of any `data-color` ancestor, so chips can be
 * tinted per row without overriding tokens.
 */
export const Colors: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div
      style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem"
    >
      ${Object.entries(Color).map(
        ([name, value]) =>
          html`<craft-chip data-color="${value}">
            ${name}
            <craft-button size="small" slot="suffix" inherit variant="plain"
              >Button</craft-button
            >
          </craft-chip>`
      )}
    </div>
  `,
};

/**
 * Every slot at once. The first chip fills the built-in prefix slots —
 * `thumbnail`, `icon`, and `status` — each of which needs its own attribute
 * before it renders. The second fills `prefix` instead, which replaces that
 * whole region, so the built-in slots are ignored. Both fill the default slot
 * and `suffix`.
 *
 * The third stacks several lines in the default slot — a name, a handle, and
 * a row of indicators — the way an entry type chip does on a section's
 * settings page. It sets `align-items="start"`, so its prefix and suffix sit
 * against the first line rather than centered against the whole body.
 */
export const KitchenSink: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div
      style="display: flex; flex-direction: column; gap: 1rem; align-items: start"
    >
      <craft-chip
        selectable
        select-label="Select Homepage"
        show-thumb
        show-status
        icon="file"
      >
        <img slot="thumbnail" src="https://picsum.photos/120/120" alt="" />
        <craft-icon slot="icon" name="lightbulb"></craft-icon>
        <craft-status slot="status" status="live"></craft-status>
        Built-in prefix slots
        <craft-action-menu slot="suffix">
          <craft-button
            slot="invoker"
            label="Actions"
            size="small"
            variant="plain"
          >
            <craft-icon name="ellipsis" label="Actions"></craft-icon>
          </craft-button>
          <craft-action-item>Action Item</craft-action-item>
        </craft-action-menu>
      </craft-chip>

      <craft-chip>
        <craft-badge
          fill="info"
          slot="prefix"
          style="margin-inline: var(--_chip-spacing)"
          >Badge</craft-badge
        >
        Custom prefix
        <craft-action-menu slot="suffix">
          <craft-button
            slot="invoker"
            label="Actions"
            size="small"
            variant="plain"
          >
            <craft-icon name="ellipsis" label="Actions"></craft-icon>
          </craft-button>
          <craft-action-item>Action Item</craft-action-item>
        </craft-action-menu>
      </craft-chip>

      <craft-chip align-items="start" data-color="blue" icon="newspaper">
        <div style="display: grid; gap: 0.25rem; justify-items: start">
          <div style="display: flex; gap: 0.25rem">
            <strong>Article</strong>
            <craft-info-icon>Long-form posts for the blog.</craft-info-icon>
          </div>
          <code style="font-size: 0.85em">article</code>
          <div style="display: flex; gap: 0.25rem">
            <craft-icon name="pencil" label="Name overridden"></craft-icon>
            <craft-icon name="language" label="Translatable"></craft-icon>
          </div>
        </div>
        <div
          slot="suffix"
          style="display: flex; gap: 0.125rem; align-items: center"
        >
          <craft-action-menu>
            <craft-button
              slot="invoker"
              label="Actions"
              size="small"
              variant="plain"
            >
              <craft-icon name="ellipsis" label="Actions"></craft-icon>
            </craft-button>
            <craft-action-item>Action Item</craft-action-item>
          </craft-action-menu>
          <craft-reorder-button variant="inherit"></craft-reorder-button>
        </div>
      </craft-chip>
    </div>
  `,
};

const stressTestMenu = () => html`
  <craft-action-menu slot="suffix">
    <craft-button slot="invoker" label="Actions" size="small" variant="plain">
      <craft-icon name="ellipsis" label="Actions"></craft-icon>
    </craft-button>
    <craft-action-item>Action Item</craft-action-item>
  </craft-action-menu>
`;

const stressTestThumb = (seed: string) =>
  html`<img
    slot="thumbnail"
    src="https://picsum.photos/seed/${seed}/120/120"
    alt=""
  />`;

const stressTestStatus = (status = 'live') =>
  html`<craft-status slot="status" status=${status || nothing}></craft-status>`;

/** Each leading part, rendered with and without a suffix. */
const stressTestLeads = [
  {name: 'Label only', attrs: {}, content: () => nothing},
  {
    name: 'Status',
    attrs: {showStatus: true},
    content: () => stressTestStatus(),
  },
  {name: 'Icon', attrs: {icon: 'file'}, content: () => nothing},
  {
    name: 'Thumbnail',
    attrs: {showThumb: true},
    content: () => stressTestThumb('leads'),
  },
  {
    name: 'Avatar',
    attrs: {showThumb: true},
    content: () =>
      html`<craft-avatar slot="thumbnail" label="Ada Lovelace"></craft-avatar>`,
  },
  {
    name: 'Thumbnail and status',
    attrs: {showThumb: true, showStatus: true},
    content: () =>
      html`${stressTestThumb('both')}${stressTestStatus('pending')}`,
  },
  {name: 'Selectable', attrs: {selectable: true}, content: () => nothing},
  {
    name: 'Selectable with thumbnail',
    attrs: {selectable: true, showThumb: true},
    content: () => stressTestThumb('select'),
  },
  {
    name: 'Custom prefix',
    attrs: {},
    content: () =>
      html`<craft-badge
        fill="info"
        slot="prefix"
        style="margin-inline: var(--_chip-spacing)"
        >Badge</craft-badge
      >`,
  },
];

type StressTestChip = {
  caption: string;
  chip: unknown;
};

/** A chip from the leads above, so every group varies one thing at a time. */
const stressTestChip = (
  lead: (typeof stressTestLeads)[number],
  {
    label = 'Homepage',
    suffix = true,
    size,
    appearance,
    variant,
    selected = false,
    color,
  }: {
    label?: unknown;
    suffix?: boolean;
    size?: string;
    appearance?: string;
    variant?: string;
    selected?: boolean;
    color?: string;
  } = {}
) => {
  const attrs = lead.attrs as {
    showStatus?: boolean;
    showThumb?: boolean;
    icon?: string;
    selectable?: boolean;
  };

  return html`<craft-chip
    ?show-status=${attrs.showStatus}
    ?show-thumb=${attrs.showThumb}
    ?selectable=${attrs.selectable}
    ?selected=${selected}
    icon=${attrs.icon ?? nothing}
    size=${size ?? nothing}
    appearance=${appearance ?? nothing}
    variant=${variant ?? nothing}
    data-color=${color ?? nothing}
    select-label="Select Homepage"
  >
    ${lead.content()} ${label} ${suffix ? stressTestMenu() : nothing}
  </craft-chip>`;
};

const leadNamed = (name: string) =>
  stressTestLeads.find((lead) => lead.name === name)!;

const stressTestGroups: {heading: string; chips: StressTestChip[]}[] = [
  {
    heading: 'Leading parts',
    chips: stressTestLeads.flatMap((lead) => [
      {caption: lead.name, chip: stressTestChip(lead, {suffix: false})},
      {
        caption: `${lead.name} + suffix`,
        chip: stressTestChip(lead),
      },
    ]),
  },
  {
    heading: 'Sizes',
    chips: chipSizes.flatMap(({size, label}) =>
      ['Status', 'Icon', 'Thumbnail'].map((name) => ({
        caption: `${label}, ${name.toLowerCase()}`,
        chip: stressTestChip(leadNamed(name), {size}),
      }))
    ),
  },
  {
    heading: 'Appearances',
    chips: ['solid', 'fill', 'outline-fill', 'outline', 'plain'].flatMap(
      (appearance) => [
        {
          caption: `${appearance}, info`,
          chip: stressTestChip(leadNamed('Status'), {
            appearance,
            variant: 'info',
          }),
        },
        {
          caption: `${appearance}, default`,
          chip: stressTestChip(leadNamed('Thumbnail'), {appearance}),
        },
      ]
    ),
  },
  {
    heading: 'Selected',
    chips: [
      {
        caption: 'Default',
        chip: stressTestChip(leadNamed('Selectable'), {selected: true}),
      },
      {
        caption: 'With thumbnail',
        chip: stressTestChip(leadNamed('Selectable with thumbnail'), {
          selected: true,
        }),
      },
      {
        caption: 'Plain',
        chip: stressTestChip(leadNamed('Selectable'), {
          selected: true,
          appearance: 'plain',
        }),
      },
      {
        caption: 'Fill, info',
        chip: stressTestChip(leadNamed('Selectable'), {
          selected: true,
          appearance: 'fill',
          variant: 'info',
        }),
      },
    ],
  },
  {
    heading: 'Statuses',
    chips: ['live', 'pending', 'expired', 'disabled', 'enabled', ''].map(
      (status) => ({
        caption: status || 'Unset',
        chip: stressTestChip(
          {
            name: 'Status',
            attrs: {showStatus: true},
            content: () => stressTestStatus(status),
          },
          {label: status ? status[0]!.toUpperCase() + status.slice(1) : 'Draft'}
        ),
      })
    ),
  },
  {
    heading: 'Labels',
    chips: [
      {
        caption: 'Link',
        chip: stressTestChip(leadNamed('Status'), {
          label: html`<a href="#">Homepage</a>`,
        }),
      },
      {
        caption: 'Truncated',
        // craft-truncate truncates against its own width, so it needs the bound.
        chip: stressTestChip(leadNamed('Thumbnail'), {
          label: html`<craft-truncate style="max-width: 8rem"
            >A much longer entry title that will not fit</craft-truncate
          >`,
        }),
      },
      {
        caption: 'Badge in the label',
        chip: stressTestChip(leadNamed('Icon'), {
          label: html`Homepage <craft-badge fill="amber">Draft</craft-badge>`,
        }),
      },
      {
        caption: 'Single character',
        chip: stressTestChip(leadNamed('Status'), {label: 'A'}),
      },
    ],
  },
  {
    heading: 'Colors',
    chips: ['red', 'amber', 'green', 'blue', 'violet', 'gray'].map((color) => ({
      caption: color,
      chip: stressTestChip(leadNamed('Status'), {
        color,
        label: color[0]!.toUpperCase() + color.slice(1),
      }),
    })),
  },
];

/**
 * The chip in as many combinations as fit on one page, for checking spacing
 * and color across them at a glance. Each group varies one thing — the
 * leading part, size, appearance, selection, status, label, or color — and
 * keeps the rest at their defaults.
 */
export const StressTest: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: grid; gap: 2rem">
      ${stressTestGroups.map(
        ({heading, chips}) => html`
          <section style="display: grid; gap: 0.75rem">
            <h3 style="margin: 0">${heading}</h3>
            <div style="display: grid; gap: 1rem">
              ${chips.map(
                ({caption, chip}) => html`
                  <div
                    style="display: grid; gap: 0.25rem; justify-items: start"
                  >
                    ${chip}
                    <small style="color: var(--c-text-quiet)">${caption}</small>
                  </div>
                `
              )}
            </div>
          </section>
        `
      )}
    </div>
  `,
};

/** The parts a chip can have, each switched on or off in `Permutations`. */
const permutationParts = [
  'selectable',
  'prefix',
  'thumbnail',
  'icon',
  'status',
  'suffix',
] as const;

type PermutationPart = (typeof permutationParts)[number];

/** A square image inlined so the story renders without network access. */
const permutationThumb = `data:image/svg+xml,${encodeURIComponent(
  '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120"><defs><linearGradient id="g" x2="1" y2="1"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#ec4899"/></linearGradient></defs><rect width="120" height="120" fill="url(#g)"/></svg>'
)}`;

/** Every combination of parts, each one on or off. */
const permutationCombos = Array.from(
  {length: 2 ** permutationParts.length},
  (_, mask) => permutationParts.filter((_, i) => mask & (2 ** i))
);

const permutationAppearances = [
  'solid',
  'fill',
  'outline-fill',
  'outline',
  'plain',
] as const;

const permutationChip = (
  size: string,
  appearance: string,
  parts: readonly PermutationPart[]
) => {
  const has = (part: PermutationPart) => parts.includes(part);
  const description = parts.join(', ') || 'label only';

  return html`<craft-chip
    size="${size}"
    appearance="${appearance}"
    ?selectable=${has('selectable')}
    ?show-thumb=${has('thumbnail')}
    ?show-status=${has('status')}
    icon=${has('icon') ? 'file' : nothing}
    select-label="Select Homepage"
    title="${description}"
  >
    ${has('prefix')
      ? html`<craft-badge fill="amber" slot="prefix">Badge</craft-badge>`
      : nothing}
    ${has('thumbnail')
      ? html`<img slot="thumbnail" src="${permutationThumb}" alt="" />`
      : nothing}
    ${has('status')
      ? html`<craft-status slot="status" status="live"></craft-status>`
      : nothing}
    Homepage
    ${has('suffix')
      ? html`<craft-button icon size="small" variant="plain" slot="suffix">
          <craft-icon name="ellipsis" label="Actions"></craft-icon>
        </craft-button>`
      : nothing}
  </craft-chip>`;
};

/**
 * One chip for every combination of size, appearance, and parts — 4 sizes ×
 * 5 appearances × 64 part combinations, 1,280 chips in all. That many at once
 * is too heavy for the browser, so it shows one size at a time — 320 chips —
 * chosen with the `size` control. Hover a chip to see its parts.
 *
 * Color, selection, `align-items`, and `full-width` are left out: they would
 * multiply the count without changing the spacing between parts, and have
 * stories of their own.
 */
export const Permutations: Story = {
  args: {size: 'small', appearance: 'all'},
  argTypes: {
    size: {
      control: 'select',
      options: chipSizes.map(({size}) => size),
    },
    appearance: {
      control: 'select',
      options: ['all', ...permutationAppearances],
    },
  },
  parameters: {controls: {include: ['size', 'appearance']}},
  render: ({size, appearance}) => {
    const appearances =
      appearance === 'all' ? permutationAppearances : [appearance as string];

    return html`
      <div style="display: grid; gap: 2rem">
        ${appearances.map(
          (appearance) => html`
            <section style="display: grid; gap: 0.75rem">
              <h3 style="margin: 0">${size}, ${appearance}</h3>
              <div
                style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center"
              >
                ${permutationCombos.map((parts) =>
                  permutationChip(size, appearance, parts)
                )}
              </div>
            </section>
          `
        )}
      </div>
    `;
  },
};
