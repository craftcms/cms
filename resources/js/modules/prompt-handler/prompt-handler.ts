import {Base, Modal, type ModalSettings} from '@craftcms/garnish';

declare const Craft: any;

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
 * `Base`. Shows a modal asking the user to resolve a conflict (e.g. a filename
 * clash on upload), one prompt at a time across a batch, with an optional
 * "apply to remaining" checkbox. Booted imperatively by `Craft.AssetIndex`
 * (`new Craft.PromptHandler()`), so the class is exposed on `window.Craft`.
 *
 * jQuery-free, on the modern `@craftcms/garnish` `Modal`. One adaptation from
 * the legacy version: it poked the shared static `Garnish.Modal.$shade` to make
 * a shade click cancel the prompt; the modern `$shade` is per-instance, so that
 * listener is wired once at modal creation with `hideOnShadeClick:false`.
 */
export class PromptHandler extends Base {
  modal: Modal | null = null;

  #modalContainer: HTMLElement | null = null;
  #prompt: HTMLElement | null = null;
  #promptMessage: HTMLElement | null = null;
  #promptChoices: HTMLElement | null = null;
  #applyToRemainingContainer: HTMLElement | null = null;
  #applyToRemainingCheckbox: HTMLInputElement | null = null;
  #applyToRemainingLabel: HTMLElement | null = null;
  #promptButtons: HTMLElement | null = null;

  #prompts: PromptItem[] = [];
  #promptBatchCallback: PromptBatchCallback = () => {};
  #promptBatchReturnData: PromptItem[] = [];
  #promptBatchNum = 0;
  #promptCallback: PromptSelectionCallback = () => {};

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

    if (this.modal === null) {
      this.modal = new Modal(
        Object.assign(
          {closeOtherModals: false, hideOnShadeClick: false},
          modalSettings
        )
      );

      // Legacy poked the shared static `Garnish.Modal.$shade`; the modern shade
      // is per-instance, so wire cancel-on-shade-click once here (paired with
      // hideOnShadeClick:false above).
      if (this.modal.$shade) {
        this.addListener(this.modal.$shade, 'click', () =>
          this._cancelPrompt()
        );
      }
    }

    if (this.#modalContainer === null) {
      this.#modalContainer = document.createElement('div');
      this.#modalContainer.className = 'modal fitted prompt-modal';
      document.body.append(this.#modalContainer);
    }

    this.#modalContainer.replaceChildren();

    this.#prompt = document.createElement('div');
    this.#prompt.className = 'body';
    this.#modalContainer.append(this.#prompt);

    this.#promptMessage = document.createElement('p');
    this.#promptMessage.className = 'prompt-msg';
    this.#prompt.append(this.#promptMessage);

    this.#promptChoices = document.createElement('div');
    this.#promptChoices.className = 'options';
    this.#prompt.append(this.#promptChoices);

    this.#applyToRemainingContainer = document.createElement('label');
    this.#applyToRemainingContainer.className = 'assets-applytoremaining';
    this.#applyToRemainingContainer.style.display = 'none';
    this.#prompt.append(this.#applyToRemainingContainer);

    this.#applyToRemainingCheckbox = document.createElement('input');
    this.#applyToRemainingCheckbox.type = 'checkbox';
    this.#applyToRemainingContainer.append(this.#applyToRemainingCheckbox);

    this.#applyToRemainingLabel = document.createElement('span');
    this.#applyToRemainingContainer.append(this.#applyToRemainingLabel);

    this.#promptButtons = document.createElement('div');
    this.#promptButtons.className = 'buttons right';
    this.#prompt.append(this.#promptButtons);

    this.modal.setContainer(this.#modalContainer);

    // Prompt messages are server-rendered and may contain markup (matches the
    // legacy `.html()`).
    this.#promptMessage.innerHTML = message;

    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'btn';
    cancelBtn.textContent = Craft.t('app', 'Cancel');
    this.#promptButtons.append(cancelBtn);

    const submitBtn = document.createElement('button');
    submitBtn.type = 'submit';
    submitBtn.className = 'btn submit disabled';
    submitBtn.textContent = Craft.t('app', 'OK');
    this.#promptButtons.append(submitBtn);

    for (const choice of choices) {
      const wrapper = document.createElement('div');
      const label = document.createElement('label');
      const input = document.createElement('input');
      input.type = 'radio';
      input.name = 'promptAction';
      input.value = choice.value;
      label.append(input, ` ${choice.title}`);
      wrapper.append(label);
      this.#promptChoices.append(wrapper);

      this.addListener(input, 'click', () => {
        submitBtn.classList.remove('disabled');
      });
    }

    this.addListener(submitBtn, 'activate', () => {
      const checked = this.#modalContainer?.querySelector<HTMLInputElement>(
        'input[name=promptAction]:checked'
      );
      const choice = checked?.value ?? '';
      const applyToRemaining = !!this.#applyToRemainingCheckbox?.checked;

      this._selectPromptChoice(choice, applyToRemaining);
    });

    this.addListener(cancelBtn, 'activate', () => {
      const applyToRemaining = !!this.#applyToRemainingCheckbox?.checked;
      this._selectPromptChoice('cancel', applyToRemaining);
    });

    if (itemsToGo) {
      this.#applyToRemainingContainer.style.display = '';
      this.#applyToRemainingLabel.textContent =
        ' ' +
        Craft.t('app', 'Apply this to the {number} remaining conflicts?', {
          number: itemsToGo,
        });
    }

    this.modal.show();
  }

  _selectPromptChoice(choice: string, applyToRemaining: boolean): void {
    const prompt = this.#prompt;
    const done = (): void => {
      this.modal?.hide();
      this.#promptCallback(choice, applyToRemaining);
    };

    if (!prompt) {
      done();
      return;
    }

    // Legacy used jQuery `.fadeOut('fast', …)` (200ms).
    const animation = prompt.animate([{opacity: 1}, {opacity: 0}], {
      duration: 200,
      fill: 'forwards',
    });
    animation.onfinish = done;
  }

  _cancelPrompt(): void {
    this._selectPromptChoice('cancel', true);
  }
}
