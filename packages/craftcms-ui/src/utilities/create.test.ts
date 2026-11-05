import {expect, it} from 'vite-plus/test';
import '../components/button/button.js';
import {create} from './create.js';

it('sets properties, attributes and children', async () => {
  const button = create(
    'craft-button',
    {
      variant: 'primary',
      type: 'submit',
      attrs: {'data-id': 5, 'aria-busy': true, hidden: false, title: null},
    },
    'Save'
  );
  document.body.append(button);
  await button.updateComplete;

  expect(button.variant).toBe('primary');
  expect(button.type).toBe('submit');
  expect(button.getAttribute('data-id')).toBe('5');
  expect(button.getAttribute('aria-busy')).toBe('');
  expect(button.hasAttribute('hidden')).toBe(false);
  expect(button.hasAttribute('title')).toBe(false);
  expect(button.textContent).toBe('Save');

  button.remove();
});

it('skips undefined properties', () => {
  const value: string | undefined = undefined;

  expect(create('input', {name: 'url', value}).value).toBe('');
});

it('rejects properties the element does not have', () => {
  // Only type-checked; never called.
  const check = () => {
    // @ts-expect-error `varient` isn't a craft-button property
    create('craft-button', {varient: 'primary'});
    // @ts-expect-error methods can't be passed as properties
    create('input', {focus: () => {}});
    // @ts-expect-error read-only properties can't be set
    create('input', {validity: {} as ValidityState});
  };

  expect(check).toBeTypeOf('function');
});
