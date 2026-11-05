import {describe, expect, it} from 'vite-plus/test';
import {canonical} from '@/modules/ui/runtime';
import type {UiNodePayload, UiPayload, UiProperties} from '@/modules/ui/types';
import {
  compareTabs,
  copyableValue,
  editedFields,
  rememberValues,
  liveRows,
  sideIconHtml,
  tabsWithChanges,
  tabsWithErrors,
  withServerTabFields,
  withoutCompareAction,
  withSideIcon,
  type CompareRow,
} from './compareLayout';

const field = (uid: string): UiNodePayload => ({
  type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
  component: 'craft:field',
  props: {label: uid, layoutUid: uid},
  control: {
    type: 'CraftCms\\Cms\\Ui\\Controls\\Text',
    component: 'craft:text',
    props: {},
    path: ['fields', uid],
    mode: 'readOnly',
    deltaGroup: ['fields', uid],
  },
});

const tab = (uid: string, label: string, fields: string[]): UiNodePayload => ({
  type: 'CraftCms\\Cms\\Ui\\Nodes\\Tab',
  component: 'craft:tab',
  uid,
  props: {label},
  children: fields.map(field),
});

const form = (nodes: UiNodePayload[]): UiPayload => ({
  scope: [],
  refreshable: false,
  nodes,
  values: {},
  errors: [],
  globalErrors: [],
});

const row = (fromUid: string | null, toUid: string | null): CompareRow => ({
  fromUid,
  toUid,
  changed: false,
});

describe('compareTabs', () => {
  it('groups the rows into the newer version’s tabs, in their order', () => {
    const to = form([
      tab('content', 'Content', ['title', 'summary']),
      tab('visit', 'Visit', ['venue']),
      tab('seo', 'SEO', ['metaTitle']),
    ]);
    const from = form([
      tab('content', 'Content', ['title', 'summary', 'venue']),
    ]);

    expect(
      compareTabs(from, to, [
        row('title', 'title'),
        row('venue', 'venue'),
        row('summary', 'summary'),
      ])
    ).toEqual([
      {
        key: 'content',
        label: 'Content',
        icon: undefined,
        rows: [0, 2],
        sides: {from: true, to: true},
        sidebar: false,
      },
      {
        key: 'visit',
        label: 'Visit',
        icon: undefined,
        rows: [1],
        sides: {from: false, to: true},
        sidebar: false,
      },
    ]);
  });

  it('puts a field only the older version has in its tab there, or the newer version’s tab of the same name', () => {
    const to = form([tab('content-2', 'Content', ['title'])]);
    const from = form([
      tab('content-1', 'Content', ['title', 'tagline']),
      tab('archive', 'Archive', ['oldNotes']),
    ]);

    expect(
      compareTabs(from, to, [
        row('title', 'title'),
        row('tagline', null),
        row('oldNotes', null),
      ])
    ).toEqual([
      {
        key: 'content-2',
        label: 'Content',
        icon: undefined,
        rows: [0, 1],
        sides: {from: true, to: true},
        sidebar: false,
      },
      {
        key: 'archive',
        label: 'Archive',
        icon: undefined,
        rows: [2],
        sides: {from: true, to: false},
        sidebar: false,
      },
    ]);
  });

  it('puts a tab only the older version has after the tab before it there', () => {
    const to = form([
      tab('content', 'Content', ['title']),
      tab('details', 'Details', ['venue']),
    ]);
    const from = form([
      tab('content', 'Content', ['title']),
      tab('programme', 'Programme', ['events']),
      tab('details', 'Details', ['venue']),
    ]);

    expect(
      compareTabs(from, to, [
        row('title', 'title'),
        row('events', null),
        row('venue', 'venue'),
      ]).map((compareTab) => compareTab.key)
    ).toEqual(['content', 'programme', 'details']);
  });

  it('notes which tabs are sidebar tabs', () => {
    const visit = {
      ...tab('visit', 'Visit', ['venue']),
      props: {label: 'Visit', sidebar: true},
    };
    const ui = form([tab('content', 'Content', ['title']), visit]);

    expect(
      compareTabs(ui, ui, [row('title', 'title'), row('venue', 'venue')]).map(
        (compareTab) => [compareTab.key, compareTab.sidebar]
      )
    ).toEqual([
      ['content', false],
      ['visit', true],
    ]);
  });

  it('keeps fields that aren’t in a tab together, unlabeled', () => {
    const to = form([field('title'), field('summary')]);

    expect(
      compareTabs(to, to, [row('title', 'title'), row('summary', 'summary')])
    ).toEqual([
      {
        key: 'tab-0',
        label: null,
        icon: undefined,
        rows: [0, 1],
        sides: {from: true, to: true},
        sidebar: false,
      },
    ]);
  });
});

describe('tabsWithErrors', () => {
  it('marks the tabs holding a field the newer version has errors in', () => {
    const rows = [
      row('title', 'title'),
      row('venue', 'venue'),
      row('notes', null),
    ];
    const tabs = [
      {
        key: 'content',
        label: 'Content',
        rows: [0],
        sides: {from: true, to: true},
        sidebar: false,
      },
      {
        key: 'visit',
        label: 'Visit',
        rows: [1, 2],
        sides: {from: true, to: true},
        sidebar: false,
      },
    ];

    expect([...tabsWithErrors(tabs, rows, ['venue'])]).toEqual(['visit']);
    expect([...tabsWithErrors(tabs, rows, ['notes'])]).toEqual([]);
  });
});

describe('tabsWithChanges', () => {
  it('marks the tabs holding a changed field, even one only the older version has', () => {
    const rows: CompareRow[] = [
      {fromUid: 'title', toUid: 'title', changed: false},
      {fromUid: 'venue', toUid: null, changed: true},
      {fromUid: null, toUid: 'notes', changed: null},
    ];
    const tab = (key: string, tabRows: number[]) => ({
      key,
      label: key,
      rows: tabRows,
      sides: {from: true, to: true},
      sidebar: false,
    });

    expect([
      ...tabsWithChanges([tab('content', [0]), tab('visit', [1, 2])], rows),
    ]).toEqual(['visit']);
    // A field shown since the page loaded may or may not have changed.
    expect([...tabsWithChanges([tab('notes', [2])], rows)]).toEqual([]);
  });
});

const changedRow = (
  fromUid: string | null,
  toUid: string | null,
  changed: boolean | null
): CompareRow => ({fromUid, toUid, changed});

describe('liveRows', () => {
  const rows = [
    changedRow('title', 'title', false),
    changedRow('venue', null, true),
    changedRow('summary', 'summary', true),
  ];

  it('leaves the rows alone while the form shows the same fields', () => {
    expect(
      liveRows(rows, form([tab('content', 'Content', ['title', 'summary'])]))
    ).toEqual(rows);
  });

  it('leaves only the older version’s field in a row once the newer one’s is hidden', () => {
    expect(
      liveRows(rows, form([tab('content', 'Content', ['title'])]))
    ).toEqual([
      changedRow('title', 'title', false),
      changedRow('venue', null, true),
      changedRow('summary', null, true),
    ]);
  });

  it('pairs a newly shown field with the older version’s, not knowing whether it changed', () => {
    expect(
      liveRows(
        rows,
        form([tab('content', 'Content', ['title', 'venue', 'summary'])])
      )
    ).toEqual([
      changedRow('title', 'title', false),
      changedRow('venue', 'venue', null),
      changedRow('summary', 'summary', true),
    ]);
  });

  it('adds a row for a newly shown field the older version doesn’t have, after the field before it', () => {
    expect(
      liveRows(
        rows,
        form([tab('content', 'Content', ['title', 'tagline', 'summary'])])
      )
    ).toEqual([
      changedRow('title', 'title', false),
      changedRow(null, 'tagline', true),
      changedRow('venue', null, true),
      changedRow('summary', 'summary', true),
    ]);
  });
});

describe('withServerTabFields', () => {
  it('fills in a tab the editor hasn’t loaded with the server’s fields for it', () => {
    const deferred: UiNodePayload = {
      ...tab('visit', 'Visit', []),
      props: {label: 'Visit', deferred: true},
    };
    const live = form([tab('content', 'Content', ['title']), deferred]);
    const server = form([
      tab('content', 'Content', ['title', 'summary']),
      tab('visit', 'Visit', ['venue']),
    ]);

    expect(
      liveRows(
        [
          changedRow('title', 'title', false),
          changedRow('venue', 'venue', true),
        ],
        withServerTabFields(live, server)
      )
    ).toEqual([
      changedRow('title', 'title', false),
      changedRow('venue', 'venue', true),
    ]);
  });
});

describe('withSideIcon', () => {
  it('puts the side’s icon before every field’s label, in tabs or not, and leaves the rest alone', () => {
    const ui = withSideIcon(
      form([field('title'), tab('visit', 'Visit', ['venue'])]),
      'to'
    );
    const prefixes = (nodes: UiNodePayload[]): unknown[] =>
      nodes.flatMap((node) =>
        node.control
          ? [node.props.headingPrefix]
          : [
              [node.props.label, node.props.headingPrefix],
              ...prefixes(node.children ?? []),
            ]
      );

    expect(prefixes(ui.nodes)).toEqual([
      sideIconHtml('to'),
      ['Visit', undefined],
      sideIconHtml('to'),
    ]);
    expect(sideIconHtml('to')).toContain('name="circle-b"');
    expect(sideIconHtml('from')).toContain('name="circle-a"');
  });
});

describe('withoutCompareAction', () => {
  const compare = {
    type: 'button',
    label: 'Compare changes',
    action: {type: 'event', name: 'craft:compare-field-changes'},
  };
  const copy = {
    type: 'button',
    label: 'Copy value',
    action: {type: 'event', name: 'craft:copy-field-value'},
  };
  const withMenu = (items: UiProperties[]): UiNodePayload => ({
    ...field('summary'),
    children: [
      {
        type: 'CraftCms\\Cms\\Ui\\Nodes\\ActionMenu',
        component: 'craft:action-menu',
        uid: 'summary:menu',
        props: {items, label: 'Field actions'},
      },
      {
        type: 'CraftCms\\Cms\\Ui\\Nodes\\CopyAttribute',
        component: 'craft:copy-attribute',
        uid: 'summary:handle',
        props: {},
      },
    ],
  });
  const menuItems = (node: UiNodePayload): unknown =>
    node.children?.find((child) => child.component === 'craft:action-menu')
      ?.props.items;

  it('drops the field’s “Compare changes” action, and the rule it leaves dangling', () => {
    expect(
      menuItems(withoutCompareAction(withMenu([compare, {type: 'hr'}, copy])))
    ).toEqual([copy]);
    expect(
      menuItems(
        withoutCompareAction(
          withMenu([
            {type: 'group', items: [compare]},
            {type: 'group', items: [copy]},
          ])
        )
      )
    ).toEqual([{type: 'group', items: [copy]}]);
  });

  it('drops the action menu when that was all it had, keeping the field’s other actions', () => {
    expect(
      withoutCompareAction(withMenu([compare])).children?.map(
        (child) => child.component
      )
    ).toEqual(['craft:copy-attribute']);
  });
});

describe('editedFields', () => {
  const nodes = [field('title'), tab('visit', 'Visit', ['venue'])];

  it('finds the fields whose values differ from those first remembered', () => {
    const remembered = new Map<string, string>();
    rememberValues(
      nodes,
      {fields: {title: 'Hello', venue: 'Hall'}},
      remembered
    );

    expect(
      editedFields(nodes, {fields: {title: 'Hello', venue: 'Hall'}}, remembered)
    ).toEqual([]);
    expect(
      editedFields(nodes, {fields: {title: 'Hi', venue: 'Hall'}}, remembered)
    ).toEqual(['title']);

    // Remembering again doesn't move the baseline to the edit.
    rememberValues(nodes, {fields: {title: 'Hi', venue: 'Hall'}}, remembered);
    expect(
      editedFields(nodes, {fields: {title: 'Hi', venue: 'Hall'}}, remembered)
    ).toEqual(['title']);
  });

  it('measures a tab that loads later against the values it loaded with', () => {
    const remembered = new Map<string, string>();
    rememberValues([field('title')], {fields: {title: 'Hello'}}, remembered);
    rememberValues(
      nodes,
      {fields: {title: 'Hello', venue: 'Hall'}},
      remembered
    );

    expect(
      editedFields(nodes, {fields: {title: 'Hello', venue: 'Hall'}}, remembered)
    ).toEqual([]);
  });
});

describe('copyableValue', () => {
  /** A field whose control says its value can be read and written, unless told otherwise. */
  const control = (
    uid: string,
    component: string,
    mode: 'editable' | 'readOnly',
    extra: Partial<NonNullable<UiNodePayload['control']>> = {}
  ): UiNodePayload => ({
    ...field(uid),
    control: {
      ...field(uid).control!,
      component,
      mode,
      readable: true,
      writable: true,
      ...extra,
    },
  });
  const older = form([control('summary', 'craft:textarea', 'readOnly')]);
  older.values = {fields: {summary: 'Opens in March'}};

  it.each([
    ['a changed field', true],
    ['an unchanged one', false],
  ])(
    'copies the older value of %s into a newer one that can take it',
    (_, changed) => {
      expect(
        copyableValue(
          {...row('summary', 'summary'), changed},
          older,
          form([control('summary', 'craft:textarea', 'editable')])
        )
      ).toEqual({value: 'Opens in March'});
    }
  );

  it.each([
    [
      'a field the newer version lacks',
      row('summary', null),
      'craft:textarea',
      'editable',
    ],
    [
      'a field that can’t be edited',
      row('summary', 'summary'),
      'craft:textarea',
      'readOnly',
    ],
    [
      'a field of another kind',
      row('summary', 'summary'),
      'craft:text',
      'editable',
    ],
  ] as const)('leaves %s alone', (_, compared, component, mode) => {
    expect(
      copyableValue(
        compared,
        older,
        form([control('summary', component, mode)])
      )
    ).toBeUndefined();
  });

  it.each([
    ['neither of whose versions says it can be copied', false, false],
    ['whose newer version can’t be written', true, false],
    ['whose older version can’t be read', false, true],
  ])('leaves a field alone %s', (_, fromReadable, toWritable) => {
    const olderForm = form([
      control('notes', 'plugin:rich-text', 'readOnly', {
        readable: fromReadable,
      }),
    ]);
    olderForm.values = {fields: {notes: '<p>Opens in March</p>'}};

    expect(
      copyableValue(
        row('notes', 'notes'),
        olderForm,
        form([
          control('notes', 'plugin:rich-text', 'editable', {
            writable: toWritable,
          }),
        ])
      )
    ).toBeUndefined();
  });

  describe('a field whose value already matches', () => {
    const newer = form([control('summary', 'craft:textarea', 'editable')]);
    newer.values = {fields: {summary: 'Opens in March'}};
    const compared = row('summary', 'summary');

    it('has nothing to copy', () => {
      expect(copyableValue(compared, older, newer)).toBeUndefined();
    });

    it('has something to copy once it’s edited away from it', () => {
      expect(
        copyableValue(compared, older, newer, canonical('Opens in May'))
      ).toEqual({value: 'Opens in March'});
    });

    it('has nothing to copy once it’s edited to match', () => {
      const differing = form([
        control('summary', 'craft:textarea', 'editable'),
      ]);
      differing.values = {fields: {summary: 'Opens in May'}};

      expect(
        copyableValue(compared, older, differing, canonical('Opens in March'))
      ).toBeUndefined();
    });
  });

  it('brings along the props the control draws a copied value with', () => {
    const relation = (mode: 'editable' | 'readOnly', ids: number[]) =>
      control('hero', 'craft:element-select', mode, {
        valueProps: ['elements'],
        props: {
          limit: mode === 'editable' ? 3 : 1,
          elements: ids.map((id) => ({id, label: `Image ${id}`})),
        },
      });
    const olderForm = form([relation('readOnly', [5])]);
    olderForm.values = {fields: {hero: [5]}};

    const copy = copyableValue(
      row('hero', 'hero'),
      olderForm,
      form([relation('editable', [6])])
    );

    expect(copy).toEqual({
      value: [5],
      props: {elements: [{id: 5, label: 'Image 5'}]},
    });
  });
});
