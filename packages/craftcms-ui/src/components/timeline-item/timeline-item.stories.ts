import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftTimelineItem from './timeline-item.js';
import {html} from 'lit';
import './timeline-item.js';
import '../icon/icon.js';

const meta = {
  title: 'Components/Timeline Item',
  component: 'craft-timeline-item',
  args: {
    last: false,
  },
  argTypes: {
    last: {
      control: {type: 'boolean'},
      description: 'Hides the continuation line after the marker',
    },
  },
  render: ({last}) => html`
    <craft-timeline-item ?last="${last}" style="max-width: 30rem;">
      <craft-icon slot="marker" name="wave-pulse"></craft-icon>
      <strong slot="heading">Timeline heading</strong>
      <time slot="meta">9:41 AM</time>
      <p style="margin: 0;">The default slot accepts arbitrary body content.</p>
      <span slot="footer">Supporting footer content</span>
    </craft-timeline-item>
  `,
} satisfies Meta<CraftTimelineItem>;

export default meta;
type Story = StoryObj<CraftTimelineItem>;

/** All regions populated, with the `last` state available as a control. */
export const Default: Story = {};

/** Optional regions are omitted entirely when their slots are empty. */
export const SlotCombinations: Story = {
  render: () => html`
    <div style="display: grid; max-width: 30rem;">
      <craft-timeline-item>
        <craft-icon slot="marker" name="text"></craft-icon>
        Body only
      </craft-timeline-item>

      <craft-timeline-item>
        <craft-icon slot="marker" name="heading"></craft-icon>
        <strong slot="heading">Heading and body</strong>
        Supporting body content.
      </craft-timeline-item>

      <craft-timeline-item>
        <craft-icon slot="marker" name="clock"></craft-icon>
        <strong slot="heading">Heading and metadata</strong>
        <time slot="meta">Yesterday at 4:32 PM</time>
        Supporting body content.
      </craft-timeline-item>

      <craft-timeline-item last>
        <craft-icon slot="marker" name="info"></craft-icon>
        Body and footer
        <span slot="footer">Supporting footer content</span>
      </craft-timeline-item>
    </div>
  `,
};

/** An ordered process uses `last` to end its connecting rail. */
export const OrderedProcess: Story = {
  render: () => html`
    <ol style="max-width: 30rem; margin: 0; padding: 0; list-style: none;">
      <li>
        <craft-timeline-item>
          <craft-icon slot="marker" name="check"></craft-icon>
          <strong slot="heading">Draft created</strong>
          <time slot="meta">9:41 AM</time>
          <p style="margin: 0;">The entry draft was created.</p>
        </craft-timeline-item>
      </li>
      <li aria-current="step">
        <craft-timeline-item>
          <craft-icon slot="marker" name="clock"></craft-icon>
          <strong slot="heading">Editorial review</strong>
          <p style="margin: 0;">Waiting for one approval.</p>
        </craft-timeline-item>
      </li>
      <li>
        <craft-timeline-item last>
          <craft-icon slot="marker" name="minus"></craft-icon>
          <strong slot="heading">Publish</strong>
        </craft-timeline-item>
      </li>
    </ol>
  `,
};

/** Activity streams can combine rich headings, body details, and timestamps. */
export const ActivityStream: Story = {
  render: () => html`
    <section style="max-width: 30rem;">
      <article>
        <craft-timeline-item>
          <craft-icon slot="marker" name="plus"></craft-icon>
          <div slot="heading"><strong>Ada</strong> created the entry draft</div>
          <time slot="meta">9:41 AM</time>
          <p style="margin: 0;">Added the launch date and summary.</p>
        </craft-timeline-item>
      </article>
      <article>
        <craft-timeline-item last>
          <craft-icon slot="marker" name="message"></craft-icon>
          <div slot="heading"><strong>Lin</strong> left a comment</div>
          <time slot="meta">10:03 AM</time>
          <blockquote style="margin: 0;">
            The introduction is ready for review.
          </blockquote>
        </craft-timeline-item>
      </article>
    </section>
  `,
};
