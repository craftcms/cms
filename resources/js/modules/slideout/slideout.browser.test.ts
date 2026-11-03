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
