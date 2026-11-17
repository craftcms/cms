import $ from 'jquery';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {Slideout} from './slideout';

beforeEach(() => {
  // The CP's config, which every control panel page sets
  vi.stubGlobal('Craft', {slideoutPosition: 'end'});
});

afterEach(() => {
  vi.unstubAllGlobals();
  document.body.innerHTML = '';
});

it('opens around its contents and tears down without jQuery', () => {
  const trigger = document.createElement('button');
  document.body.append(trigger);
  trigger.focus();

  const slideout = new Slideout('<div class="so-body">Hello</div>', {
    containerAttributes: {id: 'my-slideout'},
  });

  const container = document.getElementById('my-slideout')!;
  expect(container).toBe(slideout.container);
  expect(container.classList).toContain('slideout');
  expect(container.querySelector('.so-body')?.textContent).toBe('Hello');
  expect(slideout.outerContainer!.classList).not.toContain('hidden');
  expect(Slideout.instances['my-slideout']).toBe(slideout);
  expect(slideout.triggerElement).toBe(trigger);

  slideout.close();
  expect(slideout.outerContainer!.classList).toContain('hidden');

  slideout.destroy();
  expect(document.getElementById('my-slideout')).toBeNull();
  expect(Slideout.instances['my-slideout']).toBeUndefined();
});

it('exposes its container as a jQuery collection when jQuery is loaded', () => {
  vi.stubGlobal('jQuery', $);

  const slideout = new Slideout('<div class="so-body">Hello</div>');

  expect(slideout.$container.get(0)).toBe(slideout.container);
  expect(slideout.$container.find('.so-body').text()).toBe('Hello');
  slideout.close();
  slideout.destroy();
});

it('returns focus to the menu that opened it once the menu has closed', async () => {
  document.body.innerHTML = `
    <craft-action-menu>
      <button type="button" slot="invoker">Actions</button>
      <div slot="content"><button type="button" id="item">Edit</button></div>
    </craft-action-menu>`;
  const item = document.getElementById('item')!;
  item.focus();

  const slideout = new Slideout('<div class="so-body"></div>');
  // The menu closes as the slideout opens, hiding the item
  item.hidden = true;
  slideout.close();

  await vi.waitFor(() =>
    expect(document.activeElement?.textContent).toBe('Actions')
  );
  slideout.destroy();
});
