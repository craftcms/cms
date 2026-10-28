import '@craftcms/ui/components/tooltip/tooltip';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser/context';
import './index';

afterEach(() => {
  document.body.replaceChildren();
});

function mount(width: string) {
  const container = document.createElement('div');
  container.style.width = width;
  container.innerHTML = `<craft-element-label>
    <a href="#entry" class="label-link" style="display: block; overflow: hidden; white-space: nowrap; text-overflow: ellipsis">
      <span>A very long entry title that cannot fit</span>
    </a>
  </craft-element-label>`;
  document.body.append(container);

  const label = container.querySelector('craft-element-label')!;

  return {label, link: label.querySelector<HTMLElement>('.label-link')!};
}

it('shows the full text of a clipped label in a tooltip on the link', async () => {
  const {label, link} = mount('80px');

  await vi.waitFor(() =>
    expect(label.querySelector('craft-tooltip')).not.toBeNull()
  );
  const tooltip = label.querySelector('craft-tooltip')!;

  expect(link.parentElement).toBe(label);
  expect(link.getBoundingClientRect().width).toBeGreaterThan(0);
  expect(link.closest('[aria-hidden="true"]')).toBeNull();
  expect(tooltip.getAttribute('for')).toBe(link.id);

  await userEvent.hover(link);

  await vi.waitFor(() => {
    const content = document.getElementById(
      link.getAttribute('aria-labelledby') ?? ''
    );
    expect(content?.textContent?.trim()).toBe(
      'A very long entry title that cannot fit'
    );
    expect(content?.checkVisibility()).toBe(true);
  });
});

it('adds no tooltip when the label fits', async () => {
  const {label} = mount('1000px');

  await new Promise((resolve) => requestAnimationFrame(resolve));
  await new Promise((resolve) => requestAnimationFrame(resolve));

  expect(label.querySelector('craft-tooltip')).toBeNull();
});
