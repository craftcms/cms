import {afterEach, expect, it} from 'vite-plus/test';
import {
  addErrorsToField,
  clearErrorsFromField,
  setFocusOnErrorSummary,
} from './field-errors';

afterEach(() => {
  document.body.replaceChildren();
});

it('lists errors under a field and clears them again', () => {
  document.body.innerHTML = `
    <div class="field" id="title-field"><div class="input"></div></div>
    <craft-field id="slug-field"></craft-field>`;
  const fields = document.querySelectorAll('.field, craft-field');

  addErrorsToField(fields, ['Title is *required*.']);
  addErrorsToField(fields, ['Title is too long.']);

  const [plain, craftField] = Array.from(fields);
  expect(plain!.classList.contains('has-errors')).toBe(true);
  expect(plain!.querySelector('.input')!.className).toBe(
    'input errors prevalidate'
  );
  // Replaced, not appended to
  expect(plain!.querySelectorAll('ul.errors li')).toHaveLength(1);
  expect(plain!.querySelector('ul.errors')!.id).toBe('title-errors');
  expect(plain!.querySelector('li')!.textContent).toBe('Title is too long.');
  expect(craftField!.querySelector('ul.errors')!.slot).toBe('feedback');

  clearErrorsFromField(fields);

  expect(document.querySelectorAll('ul.errors')).toHaveLength(0);
  expect(plain!.classList.contains('has-errors')).toBe(false);
});

it('opens the tab holding the field when a summary link is clicked', () => {
  document.body.innerHTML = `
    <div id="form">
      <div class="error-summary" tabindex="-1">
        <a href="#" data-field-error-key="slug">Slug is required.</a>
      </div>
      <div role="tablist">
        <a href="#tab-2" aria-selected="false">Tab 2</a>
      </div>
      <div id="tab-2" data-id="tab-2" role="tabpanel">
        <div class="field" data-error-key="slug"></div>
      </div>
    </div>`;
  const form = document.getElementById('form')!;
  addErrorsToField(form.querySelector('.field')!, ['Slug is required.']);
  const tab = form.querySelector<HTMLAnchorElement>('[role="tablist"] a')!;
  let tabClicked = false;
  tab.addEventListener('click', (ev) => {
    ev.preventDefault();
    tabClicked = true;
  });

  setFocusOnErrorSummary(form);
  expect(document.activeElement).toBe(form.querySelector('.error-summary'));

  form.querySelector<HTMLAnchorElement>('.error-summary a')!.click();

  expect(tabClicked).toBe(true);
});
