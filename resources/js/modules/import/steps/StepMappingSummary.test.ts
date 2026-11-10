import {createApp, nextTick} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import {TEMPLATE_RULE, type ImportStep} from '@/modules/import/mapping/types';
import StepMappingSummary from './StepMappingSummary.vue';

let container: HTMLElement;
let unmount: () => void;

function mount(
  settings: ImportStep['settings'],
  limit?: number,
  labels?: Record<string, {label: string; handle: string; icon?: string | null}>
): void {
  container = document.createElement('div');
  document.body.append(container);
  const app = createApp(StepMappingSummary, {
    step: {
      uid: 'step',
      type: null,
      source: null,
      transformer: null,
      batchSize: null,
      enabled: true,
      settings,
    } as ImportStep,
    limit,
    labels,
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  unmount = () => app.unmount();
}

/** Each row's label, handle (when it has a label), and what fills it. */
function rows(): string[][] {
  return [...container.querySelectorAll('tbody tr')].map((tr) => [
    tr.querySelector('th .truncate')!.textContent!.trim(),
    tr.querySelector('th code')?.textContent!.trim() ?? '',
    tr.querySelectorAll('td')[0]!.textContent!.replace(/\s+/g, ' ').trim(),
  ]);
}

/** Each row's When empty cell. */
function whenEmpty(): string[] {
  return [...container.querySelectorAll('tbody tr')].map((tr) =>
    tr.querySelectorAll('td')[1]!.textContent!.replace(/\s+/g, ' ').trim()
  );
}

afterEach(() => {
  unmount();
  container.remove();
});

it('lists each mapped destination and what fills it, in an admin table', () => {
  mount({
    map: {
      title: 'name',
      slug: TEMPLATE_RULE,
      body: '',
      blocks: {text: {fields: {copy: 'body', image: ''}}},
      empty: {text: {fields: {copy: ''}}},
    },
  });

  expect(container.querySelector('table.cp-table')).not.toBeNull();
  expect(rows()).toEqual([
    ['title', '', 'name'],
    ['slug', '', 'Template'],
    ['blocks', '', '1 nested field'],
  ]);
});

it('shows the template a destination is filled from', () => {
  mount({map: {slug: TEMPLATE_RULE}, templates: {slug: '{title|kebab}'}});

  expect(rows()).toEqual([['slug', '', '{title|kebab}']]);
});

it('shows the mappings past its limit behind a toggle', async () => {
  mount({map: {a: 'a', b: 'b', c: 'c'}}, 2);

  const toggle = () => container.querySelector<HTMLElement>('craft-button')!;

  expect(rows()).toHaveLength(2);
  expect(toggle().textContent!.trim()).toBe('+1 more');
  expect(toggle().getAttribute('aria-expanded')).toBe('false');

  toggle().click();
  await nextTick();

  expect(rows()).toHaveLength(3);
  expect(toggle().textContent!.trim()).toBe('Show less');
  expect(toggle().getAttribute('aria-expanded')).toBe('true');
  expect(
    container
      .querySelector('.step-mapping-summary__scroll')!
      .nextElementSibling!.contains(toggle())
  ).toBe(true);

  toggle().click();
  await nextTick();

  expect(rows()).toHaveLength(2);
});

it('offers no toggle while every mapping fits', () => {
  mount({map: {a: 'a', b: 'b'}}, 2);

  expect(container.querySelector('craft-button')).toBeNull();
});

it('says when nothing is mapped yet, including for settings PHP sent as an empty list', () => {
  mount([] as unknown as ImportStep['settings']);

  expect(container.textContent!.trim()).toBe('Nothing mapped yet.');
  expect(container.querySelector('table')).toBeNull();
});

it('names each destination by its label, with its handle beside it', () => {
  mount(
    {map: {title: 'name', 'layout-element-uid': 'summary', slug: 'id'}},
    undefined,
    {
      title: {label: 'Title', handle: 'title'},
      // a custom field is keyed by its layout element's uid in the map
      'layout-element-uid': {label: 'Summary', handle: 'guideSummary'},
    }
  );

  expect(rows()).toEqual([
    ['Title', 'title', 'name'],
    ['Summary', 'guideSummary', 'summary'],
    ['slug', '', 'id'],
  ]);
});

it('badges the destinations rows are matched on as Match Keys, inline in their row', () => {
  mount({
    map: {title: 'name', slug: 'id', blocks: {text: {fields: {copy: 'body'}}}},
    matchCriteria: {
      title: '1',
      slug: '',
      blocks: {text: {fields: {copy: '1'}}},
    },
  });

  expect(
    [...container.querySelectorAll('tbody th')].map((th) =>
      Boolean(th.querySelector('craft-badge'))
    )
  ).toEqual([true, false, false]);
  expect(container.querySelector('craft-badge')!.textContent!.trim()).toBe(
    'Match key'
  );
});

it('says what happens to each destination when its value is empty', () => {
  mount({
    map: {
      title: 'name',
      readingTime: 'time',
      difficulty: 'level',
      blocks: {text: {fields: {copy: 'body'}}},
    },
    clearableItems: {readingTime: '1'},
    defaultValues: {difficulty: 'beginner'},
  });

  expect(whenEmpty()).toEqual(['Skip', 'Erase', 'Default · beginner', '—']);
});

it('passes the mapping count to its header slot', () => {
  container = document.createElement('div');
  document.body.append(container);
  const app = createApp({
    components: {StepMappingSummary},
    template:
      '<StepMappingSummary :step="step"><template #header="{count}"><h3>{{ count }} fields</h3></template></StepMappingSummary>',
    setup: () => ({
      step: {
        uid: 'step',
        type: null,
        source: null,
        transformer: null,
        batchSize: null,
        enabled: true,
        settings: {map: {a: 'a', b: 'b'}},
      },
    }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  unmount = () => app.unmount();

  expect(container.querySelector('h3')!.textContent).toBe('2 fields');
});

it('shows each destination’s field type icon, the same for a match key, which its badge marks', () => {
  mount(
    {
      map: {title: 'name', postDate: 'date', other: 'x'},
      matchCriteria: {title: '1'},
    },
    undefined,
    {
      title: {label: 'Title', handle: 'title', icon: 'i-cursor'},
      postDate: {label: 'Post Date', handle: 'postDate', icon: 'calendar'},
    }
  );

  const icons = [...container.querySelectorAll('tbody tr')].map((tr) => {
    const icon = tr.querySelector('th craft-icon') as
      | (HTMLElement & {name?: string})
      | null;

    return icon?.name ?? null;
  });

  expect(icons).toEqual(['i-cursor', 'calendar', null]);
  expect(
    [...container.querySelectorAll('tbody tr')][0]!.querySelector(
      'craft-badge'
    )!.textContent
  ).toContain('Match key');
});

it('lists every mapping with no toggle when it has no limit', () => {
  mount({map: {a: 'a', b: 'b', c: 'c', d: 'd', e: 'e', f: 'f'}}, Infinity);

  expect(rows()).toHaveLength(6);
  expect(container.querySelector('craft-button')).toBeNull();
});

it('prefixes each destination with its icon, in a slot of the same width', () => {
  mount({map: {title: 'name', other: 'x'}}, undefined, {
    title: {label: 'Title', handle: 'title', icon: 'i-cursor'},
  });

  expect(
    [...container.querySelectorAll('thead th')].map((th) =>
      th.textContent!.trim()
    )
  ).toEqual(['Destination', 'Incoming Data', 'When empty']);

  const slots = [...container.querySelectorAll('tbody th')].map((th) =>
    th.querySelector('.mapping-summary-row__icon')
  );

  // the slot comes first, and is there even without an icon so names line up
  expect(
    slots.map((slot) => slot === slot?.parentElement?.firstElementChild)
  ).toEqual([true, true]);
  expect(
    slots.map((slot) => Boolean(slot!.querySelector('craft-icon')))
  ).toEqual([true, false]);
});

it('expands a container’s nested destinations beneath it, and their own in turn', async () => {
  mount(
    {
      map: {
        blocks: {
          text: {
            title: 'content.title',
            fields: {
              copy: 'content.body',
              tips: {tip: {fields: {note: 'content.tips.note'}}},
            },
          },
        },
      },
      matchCriteria: {blocks: {text: {title: '1'}}},
      clearableItems: {blocks: {text: {fields: {copy: '1'}}}},
    },
    undefined,
    {
      blocks: {label: 'Content', handle: 'blocks', icon: 'binary'},
      'blocks.text.title': {
        label: 'Text › Title',
        handle: 'title',
        icon: 'i-cursor',
      },
      'blocks.text.fields.copy': {
        label: 'Text › Copy',
        handle: 'copy',
        icon: 'i-cursor',
      },
      'blocks.text.fields.tips': {
        label: 'Text › Tips',
        handle: 'tips',
        icon: 'binary',
      },
    }
  );

  const toggle = () =>
    container.querySelector<HTMLButtonElement>('.mapping-summary-row__toggle')!;
  const labels = () =>
    [...container.querySelectorAll('tbody th .truncate')].map((span) =>
      span.textContent!.trim()
    );

  expect(labels()).toEqual(['Content']);
  expect(toggle().textContent!.trim()).toBe('3 nested fields');
  expect(toggle().getAttribute('aria-expanded')).toBe('false');

  toggle().click();
  await nextTick();

  expect(toggle().getAttribute('aria-expanded')).toBe('true');
  expect(labels()).toEqual([
    'Content',
    'Text › Title',
    'Text › Copy',
    'Text › Tips',
  ]);
  expect(
    container.querySelectorAll('tbody .mapping-summary-row--nested')
  ).toHaveLength(3);
  // a nested destination's own match criterion and When empty rule
  expect(rows()[1]).toEqual(['Text › Title', 'title', 'content.title']);
  expect(
    [...container.querySelectorAll('tbody tr')][1]!.querySelector('craft-badge')
  ).not.toBeNull();
  // a match key has no When empty rule
  expect(whenEmpty().slice(1)).toEqual(['—', 'Erase', '—']);

  // a nested container expands too, with a fallback label when none is known
  [
    ...container.querySelectorAll<HTMLButtonElement>(
      '.mapping-summary-row__toggle'
    ),
  ][1]!.click();
  await nextTick();

  expect(labels()).toEqual([
    'Content',
    'Text › Title',
    'Text › Copy',
    'Text › Tips',
    'tip › note',
  ]);

  toggle().click();
  await nextTick();

  expect(labels()).toEqual(['Content']);
});
