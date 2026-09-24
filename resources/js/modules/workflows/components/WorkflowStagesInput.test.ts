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
    label: 'User Review',
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

  function mount(
    stages: WorkflowStage[],
    errors: CraftCms.Cms.Form.FormPayload['errors'] = []
  ) {
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
          errors,
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

  async function selectType(label: string): Promise<void> {
    const menu =
      container!.querySelectorAll<CraftActionMenu>('craft-action-menu')[1]!;
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

    const removeAction = [
      ...container!.querySelectorAll('craft-action-item'),
    ].find((item) => item.textContent === 'Remove stage') as
      | (HTMLElement & {disabled: boolean})
      | undefined;
    expect(removeAction?.disabled).toBe(true);
    await selectType('User Review');

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

    const firstRemoveAction = [
      ...container!.querySelectorAll('craft-action-item'),
    ].find((item) => item.textContent === 'Remove stage') as HTMLElement;
    firstRemoveAction.click();
    expect(updates.at(-1)?.map(({uid}) => uid)).toEqual(['new-stage']);
  });

  it('shows a stage name validation error on its field', async () => {
    mount(
      [stage('editorial', '')],
      [
        {
          path: ['stages', '0', 'name'],
          messages: ['The name field is required.'],
        },
      ]
    );
    await nextTick();

    const nameInput = container!.querySelector('craft-input');

    expect(nameInput?.getAttribute('has-feedback-for')).toBe('error');
    expect(nameInput?.textContent).toContain('The name field is required.');
  });
});
