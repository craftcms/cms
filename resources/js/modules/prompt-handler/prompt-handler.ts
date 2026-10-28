import {Base, type ModalSettings} from '@craftcms/garnish';

declare const Craft: any;

/** A `craft-radio-group`; its `modelValue` is the checked radio's value. */
type ChoiceGroup = HTMLElement & {modelValue: string};

/** A `craft-checkbox`. */
type ApplyToRemainingCheckbox = HTMLElement & {checked: boolean; label: string};

/** A `craft-dialog`. */
type PromptDialog = HTMLElement & {
  label: string;
  noHeader: boolean;
  opened: boolean;
};

interface PromptChoice {
  value: string;
  title: string;
}

interface Prompt {
  message: string;
  choices: PromptChoice[];
  modalSettings?: Partial<ModalSettings>;
}

/** A queued prompt; `choice` is stamped on once the user picks. */
interface PromptItem {
  prompt: Prompt;
  choice?: string;
}

type PromptBatchCallback = (returnData: PromptItem[]) => void;
type PromptSelectionCallback = (
  choice: string,
  applyToRemaining: boolean
) => void;

/**
 * PromptHandler — a port of `Craft.PromptHandler` onto `@craftcms/garnish`
 * `Base`. Shows a dialog asking the user to resolve a conflict (e.g. a filename
 * clash on upload), one prompt at a time across a batch, with an optional
 * "apply to remaining" checkbox. Booted imperatively by `Craft.AssetIndex`
 * (`new Craft.PromptHandler()`), so the class is exposed on `window.Craft`.
 *
 * Each prompt is a `craft-dialog`. Dismissing it (Escape, a click outside)
 * cancels the rest of the batch, as a shade click did in the legacy modal,
 * unless the prompt's `modalSettings.hideOnEsc` is `false`.
 */
export class PromptHandler extends Base {
  /** The open prompt's `craft-dialog`, if any. */
  modal: PromptDialog | null = null;

  #prompts: PromptItem[] = [];
  #promptBatchCallback: PromptBatchCallback = () => {};
  #promptBatchReturnData: PromptItem[] = [];
  #promptBatchNum = 0;
  #promptCallback: PromptSelectionCallback = () => {};

  constructor(
    private readonly getPromptMount: () => HTMLElement = () => document.body
  ) {
    super();
  }

  resetPrompts(): void {
    this.#prompts = [];
    this.#promptBatchCallback = () => {};
    this.#promptBatchReturnData = [];
    this.#promptBatchNum = 0;
  }

  addPrompt(prompt: PromptItem): void {
    this.#prompts.push(prompt);
  }

  getPromptCount(): number {
    return this.#prompts.length;
  }

  showBatchPrompts(callback: PromptBatchCallback): void {
    this.#promptBatchCallback = callback;
    this.#promptBatchReturnData = [];
    this.#promptBatchNum = 0;

    this._showNextPromptInBatch();
  }

  _showNextPromptInBatch(): void {
    const prompt = this.#prompts[this.#promptBatchNum]!.prompt;
    const remainingInBatch = this.#prompts.length - (this.#promptBatchNum + 1);

    this._showPrompt(
      prompt.message,
      prompt.choices,
      this._handleBatchPromptSelection.bind(this),
      remainingInBatch,
      prompt.modalSettings
    );
  }

  _handleBatchPromptSelection(choice: string, applyToRemaining: boolean): void {
    const prompt = this.#prompts[this.#promptBatchNum]!;
    const remainingInBatch = this.#prompts.length - (this.#promptBatchNum + 1);

    // Record this choice
    prompt.choice = choice;
    this.#promptBatchReturnData.push(prompt);

    // Are there any remaining items in the batch?
    if (remainingInBatch) {
      // Get ready to deal with the next prompt
      this.#promptBatchNum++;

      // Apply the same choice to the remaining items?
      if (applyToRemaining) {
        this._handleBatchPromptSelection(choice, true);
      } else {
        // Show the next prompt
        this._showNextPromptInBatch();
      }
    } else {
      // All done! Call the callback
      this.#promptBatchCallback(this.#promptBatchReturnData);
    }
  }

  _showPrompt(
    message: string,
    choices: PromptChoice[],
    callback: PromptSelectionCallback,
    itemsToGo: number,
    modalSettings?: Partial<ModalSettings>
  ): void {
    this.#promptCallback = callback;
    this.modal?.remove();

    const dialog = document.createElement('craft-dialog') as PromptDialog;
    dialog.noHeader = true;
    this.modal = dialog;

    // The choices are a radio group named by the prompt's message. Prompt
    // messages are server-rendered and may contain markup (matches the legacy
    // `.html()`).
    const choiceGroup = document.createElement(
      'craft-radio-group'
    ) as ChoiceGroup;
    choiceGroup.setAttribute('name', 'promptAction');

    const promptMessage = document.createElement('div');
    promptMessage.slot = 'label';
    promptMessage.innerHTML = message;
    choiceGroup.append(promptMessage);
    dialog.label = promptMessage.textContent?.trim() ?? '';

    for (const choice of choices) {
      const radio = document.createElement('craft-radio') as HTMLElement & {
        label: string;
        choiceValue: string;
      };
      radio.label = choice.title;
      radio.choiceValue = choice.value;
      choiceGroup.append(radio);
    }

    const applyToRemaining = document.createElement(
      'craft-checkbox'
    ) as ApplyToRemainingCheckbox;
    applyToRemaining.hidden = !itemsToGo;
    if (itemsToGo) {
      applyToRemaining.label = Craft.t(
        'app',
        'Apply this to the {number} remaining conflicts?',
        {number: itemsToGo}
      );
    }

    const body = document.createElement('div');
    body.className = 'flex flex-col gap-md';
    body.append(choiceGroup, applyToRemaining);

    const cancelBtn = document.createElement('craft-button');
    cancelBtn.slot = 'footer';
    cancelBtn.setAttribute('type', 'button');
    cancelBtn.textContent = Craft.t('app', 'Cancel');

    const submitBtn = document.createElement('craft-button') as HTMLElement & {
      disabled: boolean;
    };
    submitBtn.slot = 'footer';
    submitBtn.setAttribute('type', 'button');
    submitBtn.setAttribute('variant', 'primary');
    submitBtn.textContent = Craft.t('app', 'OK');
    submitBtn.disabled = true;

    dialog.append(body, cancelBtn, submitBtn);

    this.addListener(choiceGroup, 'model-value-changed', () => {
      submitBtn.disabled = !choiceGroup.modelValue;
    });

    this.addListener(submitBtn, 'click', () => {
      this._selectPromptChoice(
        choiceGroup.modelValue ?? '',
        applyToRemaining.checked
      );
    });

    this.addListener(cancelBtn, 'click', () => {
      this._selectPromptChoice('cancel', applyToRemaining.checked);
    });

    dialog.addEventListener('craft-before-hide', (event) => {
      if (event.target !== dialog) return;
      event.preventDefault();

      if (modalSettings?.hideOnEsc !== false) {
        this._cancelPrompt();
      }
    });

    this.getPromptMount().append(dialog);
    dialog.opened = true;
  }

  _selectPromptChoice(choice: string, applyToRemaining: boolean): void {
    const dialog = this.modal;
    this.modal = null;

    if (dialog) {
      dialog.addEventListener('craft-after-hide', () => dialog.remove(), {
        once: true,
      });
      dialog.opened = false;
    }

    this.#promptCallback(choice, applyToRemaining);
  }

  _cancelPrompt(): void {
    this._selectPromptChoice('cancel', true);
  }
}
