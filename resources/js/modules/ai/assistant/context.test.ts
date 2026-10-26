import {afterEach, describe, expect, it} from 'vite-plus/test';
import {assistantApi} from '@/bootstrap/assistant';
import {
  registerAssistantEditor,
  splitMessageContext,
  useAppliedProposals,
  useAssistantContext,
  withMessageContext,
  type AssistantEditor,
} from './context';
import {
  addElementField,
  elementContextFields,
  setElementContext,
} from './element-context';
import type {UiValues} from '@/modules/ui/types';

function editor(id: string, values: UiValues = {}): AssistantEditor {
  return {
    id,
    params: () => ({
      elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
      elementId: 42,
      draftId: null,
      siteId: 1,
      ownerId: null,
      fieldId: null,
      canonicalId: 42,
    }),
    label: () => 'Lighthouses',
    scope: () => ['editor'],
    values: () => values,
    container: () => null,
  };
}

const summary = {path: 'fields.summary', label: 'Summary'};
const intro = {path: 'fields.intro', label: 'Intro'};

describe('useAssistantContext', () => {
  afterEach(() => {
    useAssistantContext().items.value = [];
    useAssistantContext().sent.value = [];
  });

  it('adds what the { and @ expanders insert to the context', () => {
    const context = useAssistantContext();
    const expander = (id: string) =>
      assistantApi.textExpanders.find((candidate) => candidate.id === id)!;
    const page = editor('expanded');
    const close = registerAssistantEditor(page);

    expander('craft:fields').select!({
      label: 'Summary',
      value: 'Summary',
      data: {path: 'fields.summary'},
    });

    expect(elementContextFields(page)).toEqual([summary]);

    context.items.value = [];
    expander('craft:elements').select!({
      label: 'Lighthouses',
      value: 'Lighthouses',
      data: {elementType: 'Entry', elementId: 42, siteId: 1, hint: 'Entry'},
    });

    expect(elementContextFields(page)).toBeNull();

    expander('craft:elements').select!({
      label: 'Harbors',
      value: 'Harbors',
      data: {elementType: 'Entry', elementId: 7, siteId: 1, hint: 'Entry'},
    });

    expect(context.request()).toEqual([]);
    expect(context.takeForMessage().at(-1)?.reference()).toEqual({
      elementType: 'Entry',
      elementId: 7,
      siteId: 1,
      fields: null,
      namespace: [],
    });

    close();
  });

  it('sends context with one message, which keeps each item’s reference for later messages', () => {
    const context = useAssistantContext();
    context.add({
      type: 'commerce:order',
      key: 'order-42',
      label: 'Order #42',
      icon: 'cart-shopping',
      status: {fill: 'green', label: 'Paid'},
      data: {orderId: 42, total: '€10'},
      reference: () => ({orderId: 42}),
    });

    const taken = context.takeForMessage();

    expect(context.items.value).toEqual([]);
    expect(context.takeForMessage()).toEqual([]);
    expect(
      splitMessageContext(withMessageContext('Is it shipped?', taken))
    ).toEqual({
      text: 'Is it shipped?',
      context: [
        {
          type: 'commerce:order',
          ref: taken[0]!.ref,
          label: 'Order #42',
          icon: 'cart-shopping',
          status: {fill: 'green', label: 'Paid'},
          data: {orderId: 42},
        },
      ],
    });
  });

  it('collects an editor’s fields, until its whole element is added, and keeps the element with none chosen', () => {
    const lighthouses = editor('editor-1');

    addElementField(lighthouses, summary);
    addElementField(lighthouses, summary);
    addElementField(lighthouses, intro);

    expect(elementContextFields(lighthouses)).toEqual([summary, intro]);
    expect(useAssistantContext().items.value).toHaveLength(1);

    setElementContext(lighthouses, null);
    addElementField(lighthouses, summary);

    expect(elementContextFields(lighthouses)).toBeNull();

    setElementContext(lighthouses, []);

    expect(elementContextFields(lighthouses)).toEqual([]);
  });

  it('sends each element with its editor’s current values, or without them once the editor is closed', () => {
    const context = useAssistantContext();
    const lighthouses = editor('editor-1', {fields: {summary: 'Unsaved'}});
    const done = registerAssistantEditor(lighthouses);
    addElementField(lighthouses, summary);
    context.takeForMessage();

    expect(context.request()).toEqual([
      expect.objectContaining({
        type: 'craft:element',
        elementId: 42,
        namespace: ['editor'],
        values: {fields: {summary: 'Unsaved'}},
        fields: ['fields.summary'],
      }),
    ]);

    done();

    expect(context.request()).toEqual([
      expect.objectContaining({elementId: 42, namespace: [], values: {}}),
    ]);
  });
});

describe('useAppliedProposals', () => {
  afterEach(() => {
    localStorage.clear();
    delete (window as {Craft?: unknown}).Craft;
  });

  it('only remembers the changes applied in the latest 20 conversations', () => {
    window.Craft = {systemUid: 'applied-test'} as never;

    for (let thread = 1; thread <= 21; thread++) {
      useAppliedProposals(`thread-${thread}`).add(['call-1:e1:summary']);
    }

    expect(useAppliedProposals('thread-1').applied.value.size).toBe(0);
    expect(useAppliedProposals('thread-2').applied.value).toEqual(
      new Set(['call-1:e1:summary'])
    );
    expect(useAppliedProposals('thread-21').applied.value.size).toBe(1);
  });
});
