import {createApp, h} from 'vue';
import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import type {ImportStep, MappingColumn} from '@/modules/import/mapping/types';
import StepMatching from './StepMatching.vue';

function col(overrides: Partial<MappingColumn>): MappingColumn {
  return {
    handle: overrides.prefixedHandle ?? 'title',
    label: 'Title',
    prefixedHandle: 'title',
    prefixedHandleAsArray: [overrides.prefixedHandle ?? 'title'],
    isContainer: false,
    canBeMatchCriteria: true,
    canBeCleared: true,
    ...overrides,
  } as MappingColumn;
}

const title = col({});
const slug = col({
  prefixedHandle: 'slug',
  label: 'Slug',
  prefixedHandleAsArray: ['slug'],
});
const body = col({
  prefixedHandle: 'body',
  label: 'Body',
  prefixedHandleAsArray: ['body'],
  canBeMatchCriteria: false,
});

function step(settings: Record<string, unknown>): ImportStep {
  return {
    uid: 'step-1',
    type: 'CraftCms\\Cms\\Entry\\Import\\EntryImporter',
    source: 'guides.json',
    transformer: null,
    batchSize: null,
    enabled: true,
    settings,
  };
}

let app: ReturnType<typeof createApp> | null = null;
let container: HTMLElement;
let changes: Array<{matchMode: string; matchCriteria: Record<string, unknown>}>;

function mount(settings: Record<string, unknown>): void {
  app = createApp({
    render: () =>
      h(StepMatching, {
        cols: [title, slug, body],
        step: step(settings),
        targetName: 'entries',
        onChange: (next: (typeof changes)[number]) => changes.push(next),
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
}

function radios(): HTMLInputElement[] {
  return [
    ...container.querySelectorAll<HTMLInputElement>('input[type="radio"]'),
  ];
}

function criteria(): string[] {
  return [...container.querySelectorAll('craft-checkbox label')].map((label) =>
    label.textContent!.trim()
  );
}

beforeEach(() => {
  changes = [];
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app?.unmount();
  container.remove();
});

it('asks what to do with rows that already exist, creating and updating by default', () => {
  mount({map: {title: 'title'}});

  expect(container.textContent).toContain('What if a row already exists?');
  expect(radios().map((radio) => radio.value)).toEqual([
    'createOnly',
    'createAndUpdate',
    'updateOnly',
  ]);
  expect(radios().find((radio) => radio.checked)!.value).toBe(
    'createAndUpdate'
  );
});

it('reports a new choice, keeping what’s matched on', () => {
  mount({map: {title: 'title'}, matchCriteria: {title: '1'}});

  radios()[2]!.dispatchEvent(new Event('change'));

  expect(changes.at(-1)).toEqual({
    matchMode: 'updateOnly',
    matchCriteria: {title: '1'},
  });
});

it('matches on a field once it’s ticked, even when nothing was matched on before', () => {
  // as PHP sends an empty tree
  mount({map: {title: 'title'}, matchCriteria: []});

  const checkbox = container.querySelector('craft-checkbox') as HTMLElement & {
    checked: boolean;
  };
  checkbox.checked = true;
  checkbox.dispatchEvent(new CustomEvent('model-value-changed'));

  expect(changes.at(-1)).toEqual({
    matchMode: 'createAndUpdate',
    matchCriteria: {title: '1'},
  });
});

it('offers matching on the mapped fields that can be matched on', () => {
  mount({map: {title: 'title', body: 'body'}});

  expect(container.textContent).toContain('Match against');
  expect(criteria()).toEqual(['Title']);
});

it('says to map a field first when nothing can be matched on', () => {
  mount({map: {}});

  expect(criteria()).toEqual([]);
  expect(container.textContent).toContain('Map a field that can be matched on');
});
