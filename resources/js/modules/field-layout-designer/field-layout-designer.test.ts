import {afterEach, expect, it, vi} from 'vite-plus/test';
import {Base} from '@craftcms/garnish';
import {CardViewDesigner} from './card-view-designer';
import {FieldLayoutDesigner} from './field-layout-designer';

const {openSlideout} = vi.hoisted(() => ({openSlideout: vi.fn()}));

vi.mock('@/common/slideouts', () => ({openSlideout}));

afterEach(() => {
  delete (window as any).Craft;
  delete (window as any).$;
  vi.clearAllMocks();
  vi.restoreAllMocks();
  vi.useRealTimers();
});

it('waits for the jQuery seam before initializing', async () => {
  vi.useFakeTimers();
  (window as any).Craft = {Grid: class {}};
  delete (window as any).$;

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
  container.querySelector('.fld-field-library')!.prepend(search);

  (window as any).$ = vi.fn();
  await vi.advanceTimersByTimeAsync(100);

  expect(deferredInit).toHaveBeenCalledOnce();
  expect(designer.$fieldSearch).toBe(search);
});

it('adds a field returned by the field editor slideout', () => {
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
  designer.getActiveHud = vi.fn(() => null);
  (window as any).Craft = {
    getCpUrl: vi.fn(() => '/admin/settings/fields/edit'),
  };

  designer.createField();

  const options = openSlideout.mock.calls[0]![1];
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
