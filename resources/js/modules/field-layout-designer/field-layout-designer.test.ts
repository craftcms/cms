import {afterEach, expect, it, vi} from 'vite-plus/test';
import $ from 'jquery';
import {Base} from '@craftcms/garnish';
import {CardViewDesigner} from './card-view-designer';
import {FieldLayoutDesigner} from './field-layout-designer';

const {openSlideout} = vi.hoisted(() => ({openSlideout: vi.fn()}));

vi.mock('@/common/slideouts', () => ({openSlideout}));

afterEach(() => {
  delete window.Craft;
  delete window.$;
  vi.clearAllMocks();
  vi.restoreAllMocks();
  vi.useRealTimers();
});

it('waits for the jQuery seam before initializing', async () => {
  vi.useFakeTimers();
  window.Craft = Object.assign(Object.create(null), {Grid: class {}});
  delete window.$;

  const deferredInit = vi
    .spyOn(FieldLayoutDesigner.prototype, 'deferredInit')
    .mockImplementation(() => {});
  const container = document.createElement('div');
  container.innerHTML = `
    <input data-config-input value='{"tabs":[]}'>
    <div class="fld-container">
      <div class="fld-workspace">
        <div class="fld-tabs"></div>
        <craft-button command="--add-tab"></craft-button>
      </div>
      <div class="fld-library">
        <div class="fld-field-library">
          <div class="fld-field-group"></div>
        </div>
        <div class="fld-ui-library"></div>
      </div>
    </div>
  `;

  const designer = new FieldLayoutDesigner(container);
  await vi.advanceTimersByTimeAsync(100);

  expect(deferredInit).not.toHaveBeenCalled();

  const search = document.createElement('input');
  search.type = 'search';
  const library = container.querySelector('.fld-field-library');
  if (!library) throw new Error('Expected the field library fixture.');
  library.prepend(search);

  window.$ = $;
  await vi.advanceTimersByTimeAsync(100);

  expect(deferredInit).toHaveBeenCalledOnce();
  expect(designer.$fieldSearch).toBe(search);
});

it('adds a field returned by the field editor slideout', () => {
  // SAFETY: This test deliberately creates a FieldLayoutDesigner without running its DOM-heavy constructor.
  const designer = Object.create(
    FieldLayoutDesigner.prototype
  ) as FieldLayoutDesigner;
  const group = document.createElement('div');
  const addLibraryElementToActiveTab = vi.fn();
  group.classList.add('hidden');
  designer.$createFieldBtn = document.createElement('button');
  designer.$fieldGroups = [group];
  designer.refreshLibraryFields = vi.fn();
  designer.initLibraryElements = vi.fn();
  designer.addLibraryElementToActiveTab = addLibraryElementToActiveTab;
  designer.getActiveHud = vi.fn(() => undefined);
  window.Craft = Object.assign(Object.create(null), {
    getCpUrl: vi.fn(() => '/admin/settings/fields/edit'),
  });

  designer.createField();

  const call = openSlideout.mock.calls[0];
  if (!call) throw new Error('Expected createField to open a slideout.');
  const options = call[1];
  const selectorHtml = '<div class="fld-element">New field</div>';
  options.onSaved({data: {selectorHtml}});

  expect(openSlideout).toHaveBeenCalledWith(
    '/admin/settings/fields/edit',
    expect.objectContaining({opener: designer.$createFieldBtn})
  );
  expect(group.textContent).toBe('New field');
  expect(group.classList.contains('hidden')).toBe(false);
  expect(addLibraryElementToActiveTab).toHaveBeenCalledWith(
    group.firstElementChild
  );
});

it('leaves sortable checkbox teardown to its custom element', () => {
  const baseDestroy = vi
    .spyOn(Base.prototype, 'destroy')
    .mockImplementation(() => {});
  // SAFETY: This test deliberately creates a CardViewDesigner without running its DOM-heavy constructor.
  const designer = Object.create(
    CardViewDesigner.prototype
  ) as CardViewDesigner;
  designer.cancelToken = null;
  designer.sortableCheckboxSelect = document.createElement(
    'craft-sortable-checkbox-select'
  );
  designer.$container = document.createElement('div');

  expect(() => designer.destroy()).not.toThrow();
  expect(baseDestroy).toHaveBeenCalledOnce();
});
