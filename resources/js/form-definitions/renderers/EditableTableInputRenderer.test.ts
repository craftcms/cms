import {createApp, nextTick, reactive} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from '../FormDefinitionRenderer.vue';
import EditableTableInputRenderer from './EditableTableInputRenderer.vue';

const mountedApplications: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApplications.splice(0).forEach((application) => application.unmount());
  document.body.innerHTML = '';
});

describe('Editable Table Form Elements', () => {
  it('coordinates keyed columns with unkeyed rows, defaults, and nested submission names', async () => {
    const values = reactive({
      settings: {
        columns: {
          headline: {
            heading: 'Headline',
            handle: 'headline',
            type: 'singleline',
          },
          summary: {
            heading: 'Summary',
            handle: 'summary',
            type: 'singleline',
          },
        },
        defaults: [{headline: 'Lead story', summary: 'Opening summary'}],
      },
    });
    const form = mount(values);
    const [source, defaults] = tables(form);

    await vi.waitFor(() => {
      expect(defaults.shadowRoot?.querySelectorAll('th')).toHaveLength(3);
    });

    expect(source.name).toBe('settings[columns]');
    expect(source.sourceName).toBe('columns');
    expect(defaults.name).toBe('settings[defaults]');
    expect(defaults.sourceName).toBe('defaults');
    expect(source.coordinationScope).toBe(defaults.coordinationScope);
    expect(source.value).toEqual(values.settings.columns);
    expect(defaults.value).toEqual(values.settings.defaults);
    expect(
      Array.from(defaults.shadowRoot!.querySelectorAll('th'), (heading) =>
        heading.textContent?.trim()
      )
    ).toEqual(['Headline', 'Summary', 'Actions']);

    const heading = source.shadowRoot!.querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-table-cell="headline:heading"]')!;
    heading.value = 'Story title';
    heading.dispatchEvent(new Event('input', {bubbles: true, composed: true}));
    await source.updateComplete;
    await defaults.updateComplete;
    await nextTick();

    expect(values.settings.columns.headline.heading).toBe('Story title');
    expect(defaults.shadowRoot!.querySelector('th')?.textContent?.trim()).toBe(
      'Story title'
    );

    defaults.shadowRoot!.querySelector<HTMLElement>('[data-add-row]')!.click();
    await defaults.updateComplete;
    await nextTick();

    expect(values.settings.defaults).toEqual([
      {headline: 'Lead story', summary: 'Opening summary'},
      {headline: '', summary: 'New summary'},
    ]);

    const submitted = new FormData(form);

    expect(submitted.get('settings[columns][headline][heading]')).toBe(
      'Story title'
    );
    expect(submitted.get('settings[defaults][0][headline]')).toBe('Lead story');
    expect(submitted.get('settings[defaults][1][summary]')).toBe('New summary');
  });

  it('does not edit or coordinate row values while effectively read-only', async () => {
    const columns: Record<string, Record<string, string>> = {
      headline: {
        heading: 'Headline',
        handle: 'headline',
        type: 'singleline',
      },
      summary: {
        heading: 'Summary',
        handle: 'summary',
        type: 'singleline',
      },
    };
    const values = reactive({
      settings: {
        columns,
        defaults: [
          {headline: 'Existing', summary: 'Summary', retained: 'Keep'},
        ],
      },
    });
    const form = mount(values, true);
    const [source, defaults] = tables(form);

    await vi.waitFor(() => {
      expect(defaults.shadowRoot?.querySelector('craft-input')).not.toBeNull();
    });

    expect(defaults.value).toEqual([
      {headline: 'Existing', summary: 'Summary', retained: 'Keep'},
    ]);

    values.settings.columns = {
      headline: values.settings.columns.headline!,
    };
    await nextTick();
    await source.updateComplete;
    await defaults.updateComplete;

    const input =
      defaults.shadowRoot!.querySelector<HTMLElementTagNameMap['craft-input']>(
        'craft-input'
      )!;
    input.value = 'Changed';
    input.dispatchEvent(new Event('input', {bubbles: true, composed: true}));
    defaults.shadowRoot!.querySelector<HTMLElement>('[data-add-row]')!.click();
    await defaults.updateComplete;
    await nextTick();

    expect(input.readOnly).toBe(true);
    expect(values.settings.defaults).toEqual([
      {headline: 'Existing', summary: 'Summary', retained: 'Keep'},
    ]);
    expect(defaults.value).toEqual([
      {headline: 'Existing', summary: 'Summary', retained: 'Keep'},
    ]);
  });
});

function mount(
  values: Record<string, unknown>,
  readOnly = false
): HTMLFormElement {
  const registry = createCpComponentRegistry();
  const form = document.createElement('form');

  registry.register(
    'form-element:craft:editable-table-input',
    EditableTableInputRenderer
  );
  (window as any).Cp = {$components: registry};
  document.body.appendChild(form);
  const application = createApp(FormDefinitionRenderer, {
    definition: {
      elements: [
        {
          type: 'craft:editable-table-input',
          name: 'columns',
          props: {
            sourceName: 'columns',
            keyed: true,
            definesColumns: true,
            columns: [
              {key: 'heading', label: 'Heading', type: 'text'},
              {key: 'handle', label: 'Handle', type: 'text'},
              {key: 'type', label: 'Type', type: 'text'},
            ],
          },
        },
        {
          type: 'craft:editable-table-input',
          name: 'defaults',
          props: {
            sourceName: 'defaults',
            columnsFrom: 'columns',
            defaultRow: {summary: 'New summary'},
          },
        },
      ],
    },
    bindingScope: 'settings',
    values,
    errors: {},
    readOnly,
  });

  mountedApplications.push(application);
  application.mount(form);

  return form;
}

function tables(
  form: HTMLFormElement
): [
  HTMLElementTagNameMap['craft-editable-table'],
  HTMLElementTagNameMap['craft-editable-table'],
] {
  return Array.from(form.querySelectorAll('craft-editable-table')) as [
    HTMLElementTagNameMap['craft-editable-table'],
    HTMLElementTagNameMap['craft-editable-table'],
  ];
}
