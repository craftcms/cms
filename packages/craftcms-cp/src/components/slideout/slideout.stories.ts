import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';

import './slideout.js';
import '../button/button.js';
import type CraftSlideout from './slideout.js';

const meta: Meta<CraftSlideout> = {
  title: 'Components/Slideout',
  component: 'craft-slideout',
  parameters: {
    layout: 'centered',
  },
  argTypes: {
    position: {
      control: 'select',
      options: ['start', 'end'],
    },
    showHeader: {control: 'boolean'},
    showFooter: {control: 'boolean'},
    closeOnEscape: {control: 'boolean'},
    closeOnBackdropClick: {control: 'boolean'},
  },
  args: {
    position: 'end',
    showHeader: true,
    showFooter: true,
    closeOnEscape: true,
    closeOnBackdropClick: true,
  },
} satisfies Meta<CraftSlideout>;

export default meta;
type Story = StoryObj<CraftSlideout>;

export const Default: Story = {
  render: (args) => {
    function openSlideout() {
      const slideout = document.getElementById(
        'default-slideout'
      ) as CraftSlideout;
      slideout?.show();
    }

    function closeSlideout() {
      const slideout = document.getElementById(
        'default-slideout'
      ) as CraftSlideout;
      slideout?.hide();
    }

    return html`
      <craft-button @click="${openSlideout}">Open Slideout</craft-button>

      <craft-slideout
        id="default-slideout"
        position="${args.position}"
        ?showHeader="${args.showHeader}"
        ?showFooter="${args.showFooter}"
        ?closeOnEscape="${args.closeOnEscape}"
        ?closeOnBackdropClick="${args.closeOnBackdropClick}"
        label="Example Slideout"
      >
        <h3 slot="header">Slideout Header</h3>

        <p>This is the main content of the slideout.</p>
        <p>It can contain any content you need.</p>

        <div slot="footer">
          <craft-button variant="secondary" @click="${closeSlideout}"
            >Cancel</craft-button
          >
          <craft-button>Save</craft-button>
        </div>
      </craft-slideout>
    `;
  },
};

export const NestedSlideouts: Story = {
  render: () => {
    function openSlideout(id: string) {
      const slideout = document.getElementById(id) as CraftSlideout;
      slideout?.show();
    }

    function closeSlideout(id: string) {
      const slideout = document.getElementById(id) as CraftSlideout;
      slideout?.hide();
    }

    return html`
      <craft-button @click="${() => openSlideout('outer-slideout')}"
        >Open Slideout</craft-button
      >

      <craft-slideout id="outer-slideout" label="Outer Slideout">
        <h3 slot="header">Outer Slideout</h3>

        <p>This is the outer slideout.</p>
        <p>Click the button below to open a nested slideout.</p>

        <craft-button @click="${() => openSlideout('inner-slideout')}"
          >Open Nested Slideout</craft-button
        >

        <craft-slideout id="inner-slideout" label="Inner Slideout">
          <h3 slot="header">Inner Slideout</h3>

          <p>This is the inner/nested slideout.</p>
          <p>
            It should appear on top of the outer slideout and be independently
            closable.
          </p>

          <div slot="footer">
            <craft-button @click="${() => closeSlideout('inner-slideout')}"
              >Close Inner</craft-button
            >
          </div>
        </craft-slideout>

        <div slot="footer">
          <craft-button @click="${() => closeSlideout('outer-slideout')}"
            >Close</craft-button
          >
        </div>
      </craft-slideout>
    `;
  },
};

export const PositionStart: Story = {
  render: () => {
    function openSlideout() {
      const slideout = document.getElementById(
        'start-slideout'
      ) as CraftSlideout;
      slideout?.show();
    }

    return html`
      <craft-button @click="${openSlideout}"
        >Open Slideout (Start)</craft-button
      >

      <craft-slideout
        id="start-slideout"
        position="start"
        label="Start Position Slideout"
      >
        <h3 slot="header">Start Position</h3>
        <p>This slideout opens from the start (left in LTR) side.</p>
      </craft-slideout>
    `;
  },
};

export const NoHeaderOrFooter: Story = {
  render: () => {
    function openSlideout() {
      const slideout = document.getElementById(
        'minimal-slideout'
      ) as CraftSlideout;
      slideout?.show();
    }

    function closeSlideout() {
      const slideout = document.getElementById(
        'minimal-slideout'
      ) as CraftSlideout;
      slideout?.hide();
    }

    return html`
      <craft-button @click="${openSlideout}"
        >Open Minimal Slideout</craft-button
      >

      <craft-slideout
        id="minimal-slideout"
        ?showHeader="${false}"
        ?showFooter="${false}"
        label="Minimal Slideout"
      >
        <p>This slideout has no header or footer.</p>
        <p>Just the body content.</p>
        <craft-button @click="${closeSlideout}">Close</craft-button>
      </craft-slideout>
    `;
  },
};
