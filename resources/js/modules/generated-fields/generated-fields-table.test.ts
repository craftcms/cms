import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {nextTick} from 'vue';
import {cpComponentRegistry} from '@/bootstrap/components';
import {registerFormComponents} from '@/modules/forms/register';
import type {FormNodePayload, FormPayload} from '@/modules/forms/types';
import {cvdData} from '@/modules/field-layout-designer/support';
import {CraftGeneratedFieldsTable} from './index';

registerFormComponents(cpComponentRegistry);
let form: HTMLFormElement;
let table: CraftGeneratedFieldsTable;

afterEach(() => form?.remove());

async function mount(disabled = false): Promise<void> {
  const path = ['settings', 'layout', 'generatedFields'];
  const fields: FormNodePayload[] = ['name', 'handle', 'template', 'uid'].map(
    (key) => ({
      type: 'Field',
      component: 'craft:field',
      props: {label: key, labelSrOnly: true},
      control: {
        type: key === 'uid' ? 'Hidden' : 'Text',
        component: key === 'uid' ? 'craft:hidden' : 'craft:text',
        mode: disabled ? 'disabled' : 'editable',
        path: [key],
        deltaGroup: [key],
        props: {},
      },
    })
  );
  const payload: FormPayload = {
    scope: path.slice(0, -1),
    refreshable: false,
    nodes: [
      {
        type: 'Field',
        component: 'craft:field',
        props: {},
        control: {
          type: 'Table',
          component: 'craft:table',
          mode: disabled ? 'disabled' : 'editable',
          path,
          deltaGroup: path,
          props: {
            columns: {
              name: {type: 'singleline', heading: 'Name'},
              handle: {
                type: 'singleline',
                heading: 'Handle',
                autopopulate: 'name',
              },
              template: {type: 'singleline', heading: 'Template'},
              uid: {type: 'hidden'},
            },
            rowTemplate: {scope: [], refreshable: false, nodes: fields},
            allowAdd: true,
            includeRowId: 'uid',
            allowDelete: true,
            allowReorder: true,
            defaultValues: {name: '', handle: '', template: '', uid: ''},
            addRowLabel: 'Add a field',
          },
        },
      },
    ],
    values: {
      settings: {
        layout: {
          generatedFields: [
            {
              name: 'Existing',
              handle: 'existing',
              template: '{{ object.id }}',
              uid: 'existing-uid',
            },
          ],
        },
      },
    },
    errors: [],
    globalErrors: [],
  };
  form = document.createElement('form');
  const container = document.createElement('div');
  container.className = 'fld-cvd';
  table = document.createElement('craft-generated-fields-table');
  table.dataset.payload = JSON.stringify(payload);
  container.append(table);
  form.append(container);
  document.body.append(form);
  await table.ready;
  await settle();
}

async function settle(): Promise<void> {
  await nextTick();
  for (const element of form.querySelectorAll('*')) {
    if ('updateComplete' in element) await element.updateComplete;
  }
  await nextTick();
}

function button(label: string): HTMLElement {
  return [...form.querySelectorAll<HTMLElement>('craft-button')].find(
    (button) =>
      button.getAttribute('aria-label') === label ||
      button.textContent?.trim() === label
  )!;
}

async function name(index: number, value: string): Promise<void> {
  const input = form.querySelector<HTMLInputElement>(
    `input[name="settings[layout][generatedFields][${index}][name]"]`
  )!;
  input.value = value;
  input.dispatchEvent(new Event('input', {bubbles: true}));
  await settle();
}

describe('generated fields Form table', () => {
  it('preserves identities and submits ordered values while synchronizing card attributes', async () => {
    await mount();
    const card = document.createElement('div');
    card.className = 'card-view-designer';
    table.parentElement!.append(card);
    const cvd = {
      findCheckboxByValue: vi.fn((): HTMLElement | null => null),
      addCheckbox: vi.fn(),
      updateCheckboxLabel: vi.fn(),
      removeCheckbox: vi.fn(),
    };
    cvdData.set(card, cvd);
    const changed = vi.fn();
    table.addEventListener('change', changed);

    button('Add a field').click();
    await settle();
    const uid = table.serialize()[1]!.uid;
    expect(uid).toEqual(expect.any(String));
    if (typeof uid !== 'string')
      throw new Error('Expected a generated field UID.');
    expect(uid).not.toBe('');
    expect(uid).not.toBe('existing-uid');

    await name(1, 'New field');
    expect(table.serialize()[1]).toEqual({
      name: 'New field',
      handle: 'newField',
      template: '',
      uid,
    });
    expect(cvd.addCheckbox).toHaveBeenCalledWith(
      expect.objectContaining({
        value: `generatedField:${uid}`,
        labelHtml: 'New field',
      })
    );

    cvd.findCheckboxByValue.mockReturnValue(card);
    await name(1, 'Renamed field');
    expect(cvd.updateCheckboxLabel).toHaveBeenCalledWith(
      `generatedField:${uid}`,
      'Renamed field'
    );
    const reorder = table.querySelectorAll('craft-reorder-button')[1]!;
    reorder.dispatchEvent(
      new CustomEvent('craft-reorder', {detail: {direction: 'up'}})
    );
    await settle();
    expect(table.serialize().map((row) => row.uid)).toEqual([
      uid,
      'existing-uid',
    ]);
    expect(
      new FormData(form).getAll('settings[layout][generatedFields][0][uid]')
    ).toEqual([uid]);
    expect(
      new FormData(form).get('settings[layout][generatedFields][0][handle]')
    ).toBe('renamedField');
    expect(changed).toHaveBeenCalled();

    button('Delete row 1').click();
    await settle();
    expect(cvd.removeCheckbox).toHaveBeenCalledWith(`generatedField:${uid}`);
    expect(table.serialize()).toEqual([
      {
        name: 'Existing',
        handle: 'existing',
        template: '{{ object.id }}',
        uid: 'existing-uid',
      },
    ]);
  });

  it('serializes disabled fields without making them editable or submitting their inputs', async () => {
    await mount(true);
    expect(table.serialize()).toEqual([
      {
        name: 'Existing',
        handle: 'existing',
        template: '{{ object.id }}',
        uid: 'existing-uid',
      },
    ]);
    expect(
      table.querySelectorAll('craft-reorder-button, craft-button')
    ).toHaveLength(0);
    expect([...new FormData(form).keys()]).toEqual([]);
  });
});
