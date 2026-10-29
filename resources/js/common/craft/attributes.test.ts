import {afterEach, expect, it} from 'vite-plus/test';
import {setElementAttributes} from './attributes';

afterEach(() => {
  document.body.replaceChildren();
});

it('applies attributes in the shape the server renders them', () => {
  const form = document.createElement('form');
  form.setAttribute('novalidate', '');
  form.className = 'existing';

  setElementAttributes(form, {
    novalidate: false,
    hidden: null,
    autocomplete: true,
    id: 'edit-form',
    class: ['flex', 'gap-s padded'],
    data: {saveShortcut: true, delta: ['a', 'b'], skip: false},
    aria: {label: 'Edit'},
    style: {width: 120, 'z-index': 2, maxHeight: '50vh'},
    'data-options': {a: 1},
  });

  expect(form.hasAttribute('novalidate')).toBe(false);
  expect(form.getAttribute('autocomplete')).toBe('');
  expect(form.id).toBe('edit-form');
  expect([...form.classList]).toEqual(['existing', 'flex', 'gap-s', 'padded']);
  expect(form.getAttribute('data-saveShortcut')).toBe('');
  expect(form.getAttribute('data-delta')).toBe('["a","b"]');
  expect(form.hasAttribute('data-skip')).toBe(false);
  expect(form.getAttribute('aria-label')).toBe('Edit');
  expect(form.style.width).toBe('120px');
  expect(form.style.zIndex).toBe('2');
  expect(form.style.maxHeight).toBe('50vh');
  expect(form.getAttribute('data-options')).toBe('{"a":1}');
});

it('applies to every element in a jQuery-style collection or selector', () => {
  document.body.innerHTML = '<p class="x"></p><p class="x"></p>';

  setElementAttributes('.x', {title: 'Hi'});
  setElementAttributes(document.querySelectorAll('p'), {lang: 'fr'});

  for (const p of document.querySelectorAll('p')) {
    expect(p.title).toBe('Hi');
    expect(p.lang).toBe('fr');
  }
});
