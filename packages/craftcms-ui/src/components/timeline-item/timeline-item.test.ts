import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftTimelineItem from './timeline-item.js';
import styles from './timeline-item.styles.js';
import './timeline-item.js';

async function createTimelineItem(
  innerHTML = '<craft-icon slot="marker"></craft-icon><h2 slot="heading">Heading</h2><time slot="meta">Now</time><p>Content</p><div slot="footer">Details</div>',
  last = false
): Promise<CraftTimelineItem> {
  const element = document.createElement(
    'craft-timeline-item'
  ) as CraftTimelineItem;
  element.last = last;
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;

  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-timeline-item', () => {
  it('renders its populated content regions', async () => {
    const element = await createTimelineItem();

    expect(
      element.shadowRoot?.querySelector('slot[name="marker"]')
    ).not.toBeNull();
    expect(
      element.shadowRoot?.querySelector('slot[name="heading"]')
    ).not.toBeNull();
    expect(
      element.shadowRoot?.querySelector('slot[name="meta"]')
    ).not.toBeNull();
    expect(
      element.shadowRoot?.querySelector('.timeline-item__separator')
        ?.textContent
    ).toBe('•');
    expect(
      element.shadowRoot?.querySelector('slot:not([name])')
    ).not.toBeNull();
    expect(
      element.shadowRoot?.querySelector('slot[name="footer"]')
    ).not.toBeNull();
  });

  it('omits optional regions when they have no content', async () => {
    const element = await createTimelineItem('<p>Content</p>');

    expect(element.shadowRoot?.querySelector('[part="heading"]')).toBeNull();
    expect(element.shadowRoot?.querySelector('[part="meta"]')).toBeNull();
    expect(element.shadowRoot?.querySelector('[part="footer"]')).toBeNull();
  });

  it('omits the body region when the default slot has no content', async () => {
    const element = await createTimelineItem('<h2 slot="heading">Heading</h2>');

    expect(element.shadowRoot?.querySelector('[part="body"]')).toBeNull();
  });

  it('omits the metadata separator unless both heading and metadata exist', async () => {
    const element = await createTimelineItem('<time slot="meta">Now</time>');

    expect(
      element.shadowRoot?.querySelector('.timeline-item__separator')
    ).toBeNull();
  });

  it('tracks optional content added after connection', async () => {
    const element = await createTimelineItem('<p>Content</p>');
    const heading = document.createElement('h2');
    heading.slot = 'heading';
    element.append(heading);
    await new Promise((resolve) => setTimeout(resolve));
    await element.updateComplete;

    expect(
      element.shadowRoot?.querySelector('[part="heading"]')
    ).not.toBeNull();
  });

  it('reflects whether it is the final timeline item', async () => {
    const element = await createTimelineItem(undefined, true);

    expect(element.hasAttribute('last')).toBe(true);
    expect(styles.cssText).toContain(
      ':host([last]) .timeline-item::after {\n    display: none;'
    );
  });

  it('exposes its layout regions as CSS parts', async () => {
    const element = await createTimelineItem();

    expect(element.shadowRoot?.querySelector('[part="base"]')).not.toBeNull();
    expect(element.shadowRoot?.querySelector('[part="marker"]')).not.toBeNull();
    expect(
      element.shadowRoot?.querySelector('[part="content"]')
    ).not.toBeNull();
    expect(element.shadowRoot?.querySelector('[part="header"]')).not.toBeNull();
    expect(
      element.shadowRoot?.querySelector('[part="heading"]')
    ).not.toBeNull();
    expect(element.shadowRoot?.querySelector('[part="meta"]')).not.toBeNull();
    expect(element.shadowRoot?.querySelector('[part="body"]')).not.toBeNull();
    expect(element.shadowRoot?.querySelector('[part="footer"]')).not.toBeNull();
  });
});
