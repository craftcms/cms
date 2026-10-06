import {createApp, h, nextTick, ref, type App} from 'vue';
import {useForm, type InertiaForm} from '@inertiajs/vue3';
import {useInertiaFormRenderer} from './useInertiaFormRenderer';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormRenderer from './FormRenderer.vue';
import {registerFormComponents} from './register';
import {defineTableFormHost} from './table-form-host';
import type {FormPayload, FormValues} from './types';
import type {TableControlProps} from './table/types';

type TableFormValues = {
  rows: Array<{title: string}> | Record<string, {title: string}>;
};

let app: App | undefined;
let form: HTMLFormElement;

afterEach(() => {
  vi.restoreAllMocks();
  app?.unmount();
  form?.remove();
});

async function mountTable(
  keyed = false,
  refresh?: (values: FormValues) => Promise<FormPayload>,
  controlProps: Partial<TableControlProps> = {}
) {
  form = document.createElement('form');
  const host = document.createElement('div');
  form.append(host);
  document.body.append(form);
  const mutation = ref<FormValues>({});
  const errors = ref<FormPayload['errors']>([]);
  let inertiaForm!: InertiaForm<TableFormValues>;
  let advanceBaseline!: () => void;
  const source = {...payload(keyed), refreshable: Boolean(refresh)};
  Object.assign(source.nodes[0]!.control!.props, controlProps);
  if (controlProps.columns?.title?.required) {
    for (const rowForm of source.nodes[0]!.control!.forms ?? []) {
      rowForm.nodes[0]!.props.required = true;
    }
  }
  if (refresh) {
    source.nodes[0]!.control!.reactive = true;
  }
  app = createApp({
    setup() {
      inertiaForm = useForm<TableFormValues>(source.values as TableFormValues);
      const integration = useInertiaFormRenderer(inertiaForm, source);
      const sidebar = useInertiaFormRenderer(inertiaForm, null);
      advanceBaseline = () => {
        integration.advanceBaseline();
        sidebar.advanceBaseline();
      };
      return () =>
        h(FormRenderer, {
          ref: integration.renderer,
          payload: source,
          refresh,
          errors: refresh ? undefined : errors.value,
          'onUpdate:mutation': (value: FormValues) => {
            mutation.value = value;
            integration.onMutation(value);
          },
        });
    },
  });
  const registry = createCpComponentRegistry();
  registerFormComponents(registry);
  registry.install(app);
  app.mount(host);
  await settle();
  return {mutation, errors, inertiaForm, advanceBaseline};
}

async function settle() {
  await nextTick();
  for (const element of form.querySelectorAll('*')) {
    if ('updateComplete' in element) await element.updateComplete;
  }
  await nextTick();
}

function button(label: string): HTMLElement & {disabled: boolean} {
  return [
    ...form.querySelectorAll<HTMLElement & {disabled: boolean}>('craft-button'),
  ].find(
    (button) =>
      button.textContent?.trim() === label ||
      button.getAttribute('aria-label') === label
  )!;
}

function reorder(row: number, direction: 'up' | 'down') {
  const control = form.querySelectorAll('craft-reorder-button')[row]!;
  control
    .shadowRoot!.querySelector<HTMLElement>(
      `[data-action="move${direction === 'up' ? 'Up' : 'Down'}"]`
    )!
    .click();
}

function referencedText(input: HTMLElement, attribute: string): string {
  return (input.getAttribute(attribute) ?? '')
    .split(/\s+/)
    .map((id) => document.getElementById(id)?.textContent ?? '')
    .join(' ');
}

describe('Vue table rows', () => {
  it.runIf(navigator.userAgent.includes('Chrome'))(
    'drags rows with their column widths intact and submits their values in the new order',
    async () => {
      const {page} = await import('vite-plus/test/browser');
      const {mutation} = await mountTable();
      const table = form.querySelector('table')!;
      table.style.width = '600px';
      table.style.tableLayout = 'fixed';
      for (const row of form.querySelectorAll<HTMLElement>('tbody tr')) {
        row.style.height = '80px';
      }
      const source = form.querySelector('tbody tr')!;
      const widths = [...source.children].map(
        (cell) => cell.getBoundingClientRect().width
      );
      let previewWidths: number[] = [];
      const setDragImage = Reflect.get(
        DataTransfer.prototype,
        'setDragImage'
      ) as DataTransfer['setDragImage'];
      vi.spyOn(DataTransfer.prototype, 'setDragImage').mockImplementation(
        function (this: DataTransfer, image, x, y) {
          previewWidths = [...image.querySelector('tr')!.children].map(
            (cell) => cell.getBoundingClientRect().width
          );
          setDragImage.call(this, image, x, y);
        }
      );
      const target = form.querySelectorAll('tbody tr')[1]!;
      await page
        .elementLocator(form.querySelector('craft-reorder-button')!)
        .dropTo(page.elementLocator(target), {
          targetPosition: {
            x: 10,
            y: target.getBoundingClientRect().height * 0.75,
          },
        });
      await settle();

      expect(previewWidths).toHaveLength(widths.length);
      previewWidths.forEach((width, index) =>
        expect(width).toBeCloseTo(widths[index]!, 0)
      );
      expect(mutation.value.rows).toEqual([{title: 'Beta'}, {title: 'Alpha'}]);
      expect(new FormData(form).get('rows[0][title]')).toBe('Beta');
      expect(new FormData(form).get('rows[1][title]')).toBe('Alpha');
    }
  );

  it('identifies required columns visually and in their accessible labels', async () => {
    await mountTable(false, undefined, {
      columns: {title: {type: 'singleline', heading: 'Title', required: true}},
    });

    expect(form.querySelector('thead th')?.textContent).toContain('Required');
    const input = form.querySelector<HTMLInputElement>(
      'input[name="rows[0][title]"]'
    )!;
    expect(referencedText(input, 'aria-labelledby')).toContain('Required');
  });

  it('keeps new static row identities unique after an earlier row is deleted', async () => {
    const {mutation} = await mountTable(false, undefined, {includeRowId: true});
    button('Add a row').click();
    await settle();
    const firstId = new FormData(form).get('rows[2][rowId]');
    button('Delete row 1').click();
    await settle();
    button('Add a row').click();
    await settle();

    const submitted = new FormData(form);
    expect(submitted.get('rows[1][rowId]')).toBe(firstId);
    expect(submitted.get('rows[2][rowId]')).not.toBe(firstId);
    expect((mutation.value.rows as FormValues[])[1]!.rowId).toBe(firstId);
    reorder(2, 'up');
    await settle();
    expect(new FormData(form).get('rows[2][rowId]')).toBe(firstId);
  });

  it('keeps edits made during submission unsaved until their own save completes', async () => {
    const {mutation, inertiaForm, advanceBaseline} = await mountTable();
    const input = form.querySelector<HTMLInputElement>(
      'input[name="rows[0][title]"]'
    )!;
    const field = form.querySelector('craft-field')!;
    input.value = 'Submitted';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await settle();
    expect(field.getAttribute('status')).toBe('modified');
    inertiaForm.processing = true;
    input.value = 'Later edit';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await settle();
    inertiaForm.processing = false;
    advanceBaseline();
    await settle();

    expect(input.value).toBe('Later edit');
    expect(field.getAttribute('status')).toBe('modified');
    expect(mutation.value.rows).toEqual([
      {title: 'Later edit'},
      {title: 'Beta'},
    ]);
    expect(inertiaForm.isDirty).toBe(true);

    inertiaForm.processing = true;
    inertiaForm.processing = false;
    advanceBaseline();
    await settle();
    expect(field.getAttribute('status')).toBeNull();
    expect(mutation.value).toEqual({});
    expect(inertiaForm.isDirty).toBe(false);
  });

  it('clears stale cell errors when the row order changes and displays new validation errors', async () => {
    const {errors} = await mountTable();
    errors.value = [
      {path: ['rows', '0', 'title'], messages: ['Use a unique title.']},
    ];
    await settle();
    expect(form.textContent).toContain('Use a unique title.');
    const input = form.querySelector<HTMLInputElement>(
      'input[name="rows[0][title]"]'
    )!;
    expect(referencedText(input, 'aria-labelledby')).toContain('Title, row 1');
    expect(input.getAttribute('aria-invalid')).toBe('true');
    expect(referencedText(input, 'aria-describedby')).toContain(
      'Use a unique title.'
    );

    reorder(0, 'down');
    await settle();
    expect(form.textContent).not.toContain('Use a unique title.');
    expect(referencedText(input, 'aria-labelledby')).toContain('Title, row 2');
    expect(input.getAttribute('aria-invalid')).not.toBe('true');
    expect(referencedText(input, 'aria-describedby')).not.toContain(
      'Use a unique title.'
    );

    errors.value = [
      {path: ['rows', '1', 'title'], messages: ['Use a unique title.']},
    ];
    await settle();
    expect(form.querySelectorAll('tbody tr')[1]?.textContent).toContain(
      'Use a unique title.'
    );
    expect(input.getAttribute('aria-invalid')).toBe('true');
    expect(referencedText(input, 'aria-describedby')).toContain(
      'Use a unique title.'
    );
  });

  it('blocks row additions, deletion, reordering, and row-creating paste during submission without omitting cell values', async () => {
    const {inertiaForm, mutation} = await mountTable();
    inertiaForm.processing = true;
    await settle();

    expect(button('Add a row').disabled).toBe(true);
    expect(button('Delete row 1').disabled).toBe(true);
    expect(
      form.querySelector<HTMLElement & {disabled: boolean}>(
        'craft-reorder-button'
      )?.disabled
    ).toBe(true);
    button('Add a row').click();
    button('Delete row 1').click();
    form.querySelector('craft-reorder-button')!.dispatchEvent(
      new CustomEvent('craft-reorder', {
        detail: {direction: 'down'},
        bubbles: true,
      })
    );
    const paste = new Event('paste', {bubbles: true, cancelable: true});
    Object.defineProperty(paste, 'clipboardData', {
      value: {getData: () => 'One\nTwo\nThree'},
    });
    form.querySelector('tbody td')!.dispatchEvent(paste);
    await settle();

    expect(form.querySelectorAll('tbody tr')).toHaveLength(2);
    expect(mutation.value).toEqual({});
    expect(new FormData(form).get('rows[0][title]')).toBe('Alpha');
    expect(new FormData(form).get('rows[1][title]')).toBe('Beta');

    inertiaForm.processing = false;
    await settle();
    button('Add a row').click();
    await settle();
    expect(form.querySelectorAll('tbody tr')).toHaveLength(3);
  });

  it('locks row structure while a refresh is pending and unlocks it when the response arrives', async () => {
    let resolve!: (response: FormPayload) => void;
    const response = new Promise<FormPayload>((complete) => {
      resolve = complete;
    });
    await mountTable(false, async () => response);
    button('Add a row').click();
    await settle();

    expect(form.querySelectorAll('tbody tr')).toHaveLength(3);
    expect(button('Add a row').disabled).toBe(true);
    expect(button('Delete row 1').disabled).toBe(true);
    expect(
      form.querySelector<HTMLElement & {disabled: boolean}>(
        'craft-reorder-button'
      )?.disabled
    ).toBe(true);
    button('Add a row').click();
    await settle();
    expect(form.querySelectorAll('tbody tr')).toHaveLength(3);

    resolve(payload(false));
    await settle();
    button('Add a row').click();
    await settle();
    expect(form.querySelectorAll('tbody tr')).toHaveLength(4);
  });

  it('locks row structure for native submission and leaves cancelled submissions editable', async () => {
    await mountTable();
    const cancel = (event: Event) => event.preventDefault();
    form.addEventListener('submit', cancel);
    form.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
    await settle();
    expect(button('Add a row').disabled).toBe(false);

    form.removeEventListener('submit', cancel);
    form.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
    await settle();
    expect(button('Add a row').disabled).toBe(true);
    expect(new FormData(form).get('rows[0][title]')).toBe('Alpha');
  });

  it('submits namespaced PHP-hosted cells and locally added rows through native FormData', async () => {
    const registry = createCpComponentRegistry();
    registerFormComponents(registry);
    defineTableFormHost(registry);
    form = document.createElement('form');
    const host = document.createElement('craft-table-form') as HTMLElement & {
      ready: Promise<void>;
    };
    host.dataset.payload = JSON.stringify(payload(false));
    host.setAttribute('name', 'fields[details]');
    form.append(host);
    document.body.append(form);
    await host.ready;
    await settle();

    button('Add a row').click();
    await settle();
    const submitted = new FormData(form);
    expect(submitted.get('fields[details][0][title]')).toBe('Alpha');
    expect(submitted.get('fields[details][2][title]')).toBe('Default');
    expect([...submitted.keys()].some((key) => key.startsWith('rows'))).toBe(
      false
    );
  });

  it('keeps surviving inputs and focus while adding and reordering rows, and submits only ordered cell values', async () => {
    const {mutation} = await mountTable();
    const first = form.querySelector<HTMLInputElement>(
      'input[name="rows[0][title]"]'
    )!;
    first.focus();
    button('Add a row').click();
    await settle();

    const added = form.querySelector<HTMLInputElement>(
      'input[name="rows[2][title]"]'
    )!;
    expect(added).not.toBeNull();
    expect(document.activeElement).toBe(added);
    expect(form.querySelector('input[name="rows[0][title]"]')).toBe(first);

    reorder(2, 'up');
    await settle();
    expect(form.querySelector('input[name="rows[1][title]"]')).toBe(added);
    const reordered = form.querySelectorAll('craft-reorder-button')[1]!;
    expect(reordered.shadowRoot?.activeElement).toBe(
      reordered.shadowRoot?.querySelector('[slot="invoker"]')
    );
    expect(mutation.value.rows).toEqual([
      {title: 'Alpha'},
      {title: 'Default'},
      {title: 'Beta'},
    ]);

    button('Delete row 1').click();
    await settle();
    expect(form.querySelector('input[name="rows[0][title]"]')).toBe(added);
    expect(mutation.value.rows).toEqual([{title: 'Default'}, {title: 'Beta'}]);
  });

  it('preserves external keyed row identities and displays refreshed errors without replacing inputs', async () => {
    const {mutation, errors} = await mountTable(true);
    const input = form.querySelector<HTMLInputElement>(
      'input[name="rows[row7][title]"]'
    )!;
    input.focus();
    errors.value = [
      {path: ['rows', 'row7', 'title'], messages: ['Use a unique title.']},
    ];
    await settle();
    expect(form.textContent).toContain('Use a unique title.');
    expect(form.querySelector('input[name="rows[row7][title]"]')).toBe(input);
    expect(document.activeElement).toBe(input);

    reorder(0, 'down');
    await settle();
    expect(Object.keys(mutation.value.rows as FormValues)).toEqual([
      'row9',
      'row7',
    ]);
    expect(new FormData(form).get('rows[row7][title]')).toBe('Alpha');
  });
});

function payload(keyed: boolean): FormPayload {
  const control = {
    type: 'CraftCms\\Cms\\Form\\Controls\\Text',
    component: 'craft:text',
    props: {},
    path: ['title'],
    mode: 'editable' as const,
    deltaGroup: ['title'],
  };
  const node = {
    type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
    component: 'craft:field',
    props: {label: 'Title'},
    control,
  };
  return {
    scope: [],
    refreshable: false,
    globalErrors: [],
    errors: [],
    values: {
      rows: keyed
        ? {row7: {title: 'Alpha'}, row9: {title: 'Beta'}}
        : [{title: 'Alpha'}, {title: 'Beta'}],
    },
    nodes: [
      {
        type: 'CraftCms\\Cms\\Form\\Nodes\\Field',
        component: 'craft:field',
        props: {label: 'Rows'},
        control: {
          type: 'CraftCms\\Cms\\Form\\Controls\\Table',
          component: 'craft:table',
          nestsForms: true,
          path: ['rows'],
          deltaGroup: ['rows'],
          mode: 'editable',
          props: {
            columns: {title: {type: 'singleline', heading: 'Title'}},
            allowAdd: true,
            allowDelete: true,
            allowReorder: true,
            keyed,
            defaultValues: {title: 'Default'},
            rowTemplate: {scope: [], refreshable: false, nodes: [node]},
          },
          forms: (keyed ? ['row7', 'row9'] : ['0', '1']).map((key) => ({
            scope: ['rows', key],
            refreshable: false,
            nodes: [
              {
                ...node,
                control: {
                  ...control,
                  path: ['rows', key, 'title'],
                  deltaGroup: ['rows'],
                },
              },
            ],
          })),
        },
      },
    ],
  };
}
