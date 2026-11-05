import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {PromptHandler} from './prompt-handler';

type PromptDialog = HTMLElement & {opened: boolean};

const choices = [
  {value: 'keepBoth', title: 'Keep both'},
  {value: 'replace', title: 'Replace it'},
];

function openDialog(): PromptDialog {
  return [...document.body.querySelectorAll('craft-dialog')].at(
    -1
  ) as PromptDialog;
}

function choose(value: string, applyToRemaining = false): void {
  const dialog = openDialog();
  const group = dialog.querySelector('craft-radio-group') as HTMLElement & {
    modelValue: string;
  };
  group.modelValue = value;
  group.dispatchEvent(new Event('model-value-changed'));

  const checkbox = dialog.querySelector('craft-checkbox') as HTMLElement & {
    checked: boolean;
  };
  checkbox.checked = applyToRemaining;

  const [, ok] = dialog.querySelectorAll<HTMLElement>('craft-button');
  ok!.click();
}

describe('PromptHandler', () => {
  beforeEach(() => {
    (window as any).Craft = {
      t: (
        _category: string,
        message: string,
        params?: Record<string, string | number>
      ) => message.replace('{number}', String(params?.number ?? '')),
    };
  });

  afterEach(() => {
    document.body.replaceChildren();
    vi.restoreAllMocks();
  });

  it('asks about each prompt in turn and returns the choices', () => {
    const handler = new PromptHandler();
    handler.addPrompt({prompt: {message: 'a.jpg exists', choices}});
    handler.addPrompt({prompt: {message: 'b.jpg exists', choices}});
    const done = vi.fn();

    handler.showBatchPrompts(done);
    expect(openDialog().querySelector('[slot=label]')!.textContent).toBe(
      'a.jpg exists'
    );
    choose('keepBoth');

    expect(openDialog().querySelector('[slot=label]')!.textContent).toBe(
      'b.jpg exists'
    );
    choose('replace');

    expect(
      done.mock.calls[0]![0].map((p: {choice: string}) => p.choice)
    ).toEqual(['keepBoth', 'replace']);
  });

  it('applies a choice to the remaining prompts', () => {
    const handler = new PromptHandler();
    handler.addPrompt({prompt: {message: 'a.jpg exists', choices}});
    handler.addPrompt({prompt: {message: 'b.jpg exists', choices}});
    handler.addPrompt({prompt: {message: 'c.jpg exists', choices}});
    const done = vi.fn();

    handler.showBatchPrompts(done);
    choose('replace', true);

    expect(
      done.mock.calls[0]![0].map((p: {choice: string}) => p.choice)
    ).toEqual(['replace', 'replace', 'replace']);
  });

  it('cancels the batch when the prompt is dismissed', () => {
    const handler = new PromptHandler();
    handler.addPrompt({prompt: {message: 'a.jpg exists', choices}});
    handler.addPrompt({prompt: {message: 'b.jpg exists', choices}});
    const done = vi.fn();

    handler.showBatchPrompts(done);
    openDialog().dispatchEvent(
      new CustomEvent('craft-before-hide', {cancelable: true})
    );

    expect(
      done.mock.calls[0]![0].map((p: {choice: string}) => p.choice)
    ).toEqual(['cancel', 'cancel']);
  });

  it('stays open when dismissed if the prompt says not to hide on Escape', () => {
    const handler = new PromptHandler();
    handler.addPrompt({
      prompt: {
        message: 'a.jpg exists',
        choices,
        modalSettings: {hideOnEsc: false},
      },
    });
    const done = vi.fn();

    handler.showBatchPrompts(done);
    const dismissal = new CustomEvent('craft-before-hide', {cancelable: true});
    openDialog().dispatchEvent(dismissal);

    expect(dismissal.defaultPrevented).toBe(true);
    expect(done).not.toHaveBeenCalled();
    expect(openDialog().opened).toBe(true);
  });
});
