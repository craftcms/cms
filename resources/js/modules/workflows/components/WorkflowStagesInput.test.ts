import {createApp, h, nextTick, shallowRef} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftActionMenu from '@craftcms/ui/components/action-menu/action-menu';
import WorkflowStagesInput from './WorkflowStagesInput.vue';

type WorkflowStageData = CraftCms.Cms.Workflow.Data.WorkflowStageData;
type WorkflowStage = {
  -readonly [Key in keyof WorkflowStageData]: WorkflowStageData[Key];
};

const type = 'CraftCms\\Cms\\Workflow\\UserReview\\UserReviewStage';
const stageTypes = [
  {
    type,
    label: 'User review',
    settings: {approvalsRequired: 1, approvalMode: 'total', userGroups: []},
    settingsForm: null,
  },
  {
    type: 'plugin\\AutomatedStage',
    label: 'Automated review',
    settings: {rule: 'passing'},
    settingsForm: null,
  },
];

function stage(uid: string, name: string): WorkflowStage {
  return {
    uid,
    name,
    type,
    settings: {approvalsRequired: 1, approvalMode: 'total', userGroups: []},
    settingsForm: null,
  };
}

describe('WorkflowStagesInput', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  beforeEach(() => {
    vi.stubGlobal('crypto', {randomUUID: () => 'new-stage'});
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response('<svg></svg>'))
    );
  });

  afterEach(() => {
    app?.unmount();
    container?.remove();
    vi.unstubAllGlobals();
  });

  function mount(stages: WorkflowStage[]) {
    const updates: WorkflowStage[][] = [];
    const modelValue = shallowRef(stages);
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(WorkflowStagesInput as never, {
          modelValue: modelValue.value,
          stageTypes,
          editable: true,
          'onUpdate:modelValue': (value: WorkflowStage[]) => {
            modelValue.value = value;
            updates.push(value);
          },
        }),
    });
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);

    return updates;
  }

  function button(label: string): HTMLElement {
    return container!.querySelector(`[aria-label="${label}"]`)!;
  }

  async function selectType(label: string, menuIndex: number): Promise<void> {
    const menu =
      container!.querySelectorAll<CraftActionMenu>('craft-action-menu')[
        menuIndex
      ]!;
    await vi.waitFor(() =>
      expect(menu.querySelector('craft-action-item')).not.toBeNull()
    );

    const item = [
      ...menu.querySelectorAll<HTMLElement>('craft-action-item'),
    ].find((item) => item.textContent === label)!;
    item.click();
    await nextTick();
  }

  it('reorders complete stages', async () => {
    const updates = mount([
      stage('editorial', 'Editorial'),
      stage('legal', 'Legal'),
    ]);
    await nextTick();

    container!.querySelector('craft-reorder-button')!.dispatchEvent(
      new CustomEvent('reorder', {
        detail: {direction: 'down'},
        bubbles: true,
      })
    );

    expect(updates.at(-1)?.map(({uid}) => uid)).toEqual(['legal', 'editorial']);
  });

  it('adds and removes stages while retaining at least one', async () => {
    const updates = mount([stage('editorial', 'Editorial')]);
    await nextTick();

    expect((button('Remove stage') as HTMLButtonElement).disabled).toBe(true);
    await selectType('User review', 1);

    expect(updates.at(-1)).toEqual([
      stage('editorial', 'Editorial'),
      {
        uid: 'new-stage',
        name: 'Review',
        type,
        settings: {approvalsRequired: 1, approvalMode: 'total', userGroups: []},
        settingsForm: null,
      },
    ]);

    button('Remove stage').click();
    expect(updates.at(-1)?.map(({uid}) => uid)).toEqual(['new-stage']);
  });

  it('confirms and resets settings when changing stage type', async () => {
    const confirm = vi.fn(() => true);
    Object.defineProperty(window, 'confirm', {
      value: confirm,
      configurable: true,
    });
    const updates = mount([stage('editorial', 'Editorial')]);
    await nextTick();

    await selectType('Automated review', 0);

    expect(confirm).toHaveBeenCalled();
    expect(updates.at(-1)?.[0]).toEqual({
      uid: 'editorial',
      name: 'Editorial',
      type: 'plugin\\AutomatedStage',
      settings: {rule: 'passing'},
      settingsForm: null,
    });
  });

  it('labels the stage type picker', async () => {
    const settingsForm = {
      scope: [],
      refreshable: false,
      nodes: [],
      values: {},
      errors: [],
      globalErrors: [],
    };

    mount([{...stage('editorial', 'Editorial'), settingsForm}]);
    await nextTick();

    const typeField = container!.querySelector<
      HTMLElement & {label: string; updateComplete: Promise<boolean>}
    >('craft-field');
    expect(typeField).not.toBeNull();
    await typeField!.updateComplete;
    expect(typeField!.label).toBe('Type');
    expect(typeField!.getAttribute('role')).toBe('group');
    expect(typeField!.getAttribute('aria-labelledby')).not.toBeNull();
    expect(typeField!.textContent).toContain('User review');
  });
});
