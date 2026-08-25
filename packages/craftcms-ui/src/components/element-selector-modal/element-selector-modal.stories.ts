import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {expect, userEvent, waitFor} from 'storybook/test';
import {html} from 'lit';

import './element-selector-modal.js';
import type CraftElementSelectorModal from './element-selector-modal.js';
import {ElementSelectorController} from '@src/core/element-selector/index.js';

const MODAL_ID = 'storybook-element-selector-modal';

interface ModalArgs {
  label: string;
  showTitle: boolean;
  canSubmit: boolean;
  busy: boolean;
  loading: boolean;
  nonModal: boolean;
  fullscreen: boolean;
  rows: number;
}

const modal = () =>
  document.getElementById(MODAL_ID) as CraftElementSelectorModal;

/** Stands in for the real element index, which is Vue and lives in the app. */
function stubIndex(rows: number) {
  return html`
    <div
      style="display:flex;height:100%;min-height:0;border-top:1px solid var(--c-border-subtle,#ddd)"
    >
      <nav
        style="width:180px;flex:none;padding:12px;border-right:1px solid var(--c-border-subtle,#ddd);overflow:auto"
      >
        <div style="font-weight:600;margin-bottom:8px">Sources</div>
        ${['All entries', 'News', 'Pages'].map(
          (source) => html`<div style="padding:4px 0">${source}</div>`
        )}
      </nav>
      <div style="flex:1;min-width:0;display:flex;flex-direction:column">
        <div
          style="flex:none;padding:12px;border-bottom:1px solid var(--c-border-subtle,#ddd)"
        >
          Toolbar
        </div>
        <div
          style="flex:1;min-height:0;overflow:auto;padding:12px"
          tabindex="0"
          role="group"
          aria-label="Results"
        >
          ${Array.from(
            {length: rows},
            (_, i) => html`<div style="padding:6px 0">Element ${i + 1}</div>`
          )}
        </div>
      </div>
    </div>
  `;
}

const meta = {
  title: 'Components/ElementSelectorModal',
  component: 'craft-element-selector-modal',
  args: {
    label: 'Choose an entry',
    showTitle: true,
    canSubmit: false,
    busy: false,
    loading: false,
    nonModal: false,
    fullscreen: false,
    rows: 40,
  },
  parameters: {layout: 'centered'},
  render(args: ModalArgs) {
    return html`
      <craft-element-selector-modal
        id=${MODAL_ID}
        label=${args.label}
        ?show-title=${args.showTitle}
        ?can-submit=${args.canSubmit}
        ?busy=${args.busy}
        ?loading=${args.loading}
        ?non-modal=${args.nonModal}
        ?fullscreen=${args.fullscreen}
      >
        ${stubIndex(args.rows)}
      </craft-element-selector-modal>

      <craft-button @click=${() => (modal().opened = true)}>
        Open selector
      </craft-button>
    `;
  },
} satisfies Meta<ModalArgs>;

export default meta;
type Story = StoryObj<ModalArgs>;

/** Opens the story's modal and hands back its surface box. */
async function openAndMeasure(canvasElement: HTMLElement) {
  const element = canvasElement.querySelector(
    'craft-element-selector-modal'
  ) as CraftElementSelectorModal;

  element.opened = true;
  await element.updateComplete;
  await new Promise((resolve) => requestAnimationFrame(resolve));

  const shadow = element.shadowRoot!;

  return {
    element,
    surface: shadow.querySelector('.surface')!.getBoundingClientRect(),
    body: shadow.querySelector('.body') as HTMLElement,
    viewport: window.innerHeight,
  };
}

export const Default: Story = {};

/** A short result list gets a short dialog, not a tall one full of empty space. */
export const FewResults: Story = {
  args: {rows: 2},
  async play({canvasElement}) {
    const {surface, viewport} = await openAndMeasure(canvasElement);

    // Well clear of the 90dvh cap — the whole point of sizing to content.
    await expect(surface.height).toBeLessThan(viewport * 0.9 - 1);
    // ...and not collapsed below the floor.
    await expect(surface.height).toBeGreaterThanOrEqual(400);
  },
};

/** ...but never shorter than the floor, however little there is to show. */
export const NoResults: Story = {
  args: {rows: 0},
  async play({canvasElement}) {
    const {surface} = await openAndMeasure(canvasElement);

    await expect(surface.height).toBeGreaterThanOrEqual(400);
  },
};

/** A long list fills to the cap and scrolls inside rather than growing past it. */
export const ManyResults: Story = {
  args: {rows: 200},
  async play({canvasElement}) {
    const {surface, viewport} = await openAndMeasure(canvasElement);

    await expect(surface.height).toBeLessThanOrEqual(viewport * 0.9 + 1);
    await expect(surface.height).toBeGreaterThan(viewport * 0.5);
  },
};

export const WithSelection: Story = {
  args: {canSubmit: true},
};

/** Mid-save: both buttons are out, and the index is inert rather than merely dim. */
export const Busy: Story = {
  args: {canSubmit: true, busy: true},
};

export const Loading: Story = {
  args: {loading: true, rows: 0},
};

export const HiddenTitle: Story = {
  args: {showTitle: false},
};

export const Fullscreen: Story = {
  args: {fullscreen: true},
};

/**
 * Out of the top layer, so `<body>`-appended menus — most of the legacy jQuery
 * CP — still paint above it. Used by the asset-move folder picker.
 */
export const NonModal: Story = {
  args: {nonModal: true},
};

/**
 * The real thing: bound to a controller, which owns open state, the labels and
 * whether Select is available.
 */
export const BoundToAController: Story = {
  render(args: ModalArgs) {
    const controller = new ElementSelectorController({
      elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
      modalTitle: args.label,
      showTitle: true,
      hideOnSelect: false,
      loadIndexBody: async () => ({html: '', props: {}}),
      // Stands in for the opener's async work, so `busy` is observable.
      onSelect: () => new Promise<void>((resolve) => setTimeout(resolve, 1200)),
    });

    const select = () =>
      controller.setSelection([
        {
          id: 1,
          siteId: 1,
          label: 'Element 1',
          status: 'live',
          url: null,
          hasThumb: false,
        },
      ]);

    return html`
      <craft-element-selector-modal id=${MODAL_ID} .controller=${controller}>
        ${stubIndex(args.rows)}
        <craft-button
          slot="secondary-actions"
          data-testid="simulate-selection"
          @click=${select}
        >
          Simulate a selection
        </craft-button>
      </craft-element-selector-modal>

      <craft-button data-testid="open" @click=${() => controller.open()}>
        Open selector
      </craft-button>
    `;
  },
  async play({canvasElement}) {
    // Query by test id, not by tag: the first craft-button in the DOM is the
    // one slotted inside the modal, not the opener.
    await userEvent.click(
      canvasElement.querySelector('[data-testid="open"]') as HTMLElement
    );

    const element = canvasElement.querySelector(
      'craft-element-selector-modal'
    ) as CraftElementSelectorModal;

    await waitFor(() => expect(element.opened).toBe(true));

    // `showModal()` puts it in the top layer; `:modal` is the observable proof,
    // and it is the reason the non-modal escape hatch exists for legacy content.
    const dialog = element.shadowRoot!.querySelector('dialog')!;
    await expect(dialog.matches(':modal')).toBe(true);

    // Select is unavailable until the controller reports a selection.
    const selectButton = element.shadowRoot!.querySelector(
      '[part="select"]'
    ) as HTMLElement;
    await expect(selectButton.hasAttribute('disabled')).toBe(true);

    await userEvent.click(
      element.querySelector('[data-testid="simulate-selection"]') as HTMLElement
    );
    await waitFor(() =>
      expect(selectButton.hasAttribute('disabled')).toBe(false)
    );

    // Real hit-testing, not just a dispatched click: Lion's LionButton base
    // paints an absolutely-positioned :host::before 44px click target, which has
    // previously swallowed pointer events aimed at content inside a shadow root.
    // elementFromPoint is what catches that; element.click() would not.
    const box = selectButton.getBoundingClientRect();
    const hit = element.shadowRoot!.elementFromPoint(
      box.left + box.width / 2,
      box.top + box.height / 2
    );
    await expect(selectButton.contains(hit) || selectButton === hit).toBe(true);

    // Submitting holds `busy`, which makes the index inert.
    await userEvent.click(selectButton);
    await waitFor(() => expect(element.busy).toBe(true));
    await expect(
      element.shadowRoot!.querySelector('.body')!.hasAttribute('inert')
    ).toBe(true);

    await waitFor(() => expect(element.busy).toBe(false), {timeout: 3000});
  },
};
