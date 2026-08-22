import {Base} from '@craftcms/garnish';

// Keeps the `declare const $` / `declare const Craft` seams: the element index
// (`Craft.currentElementIndex`) is still the legacy P4a class, and the trigger
// element + the `$selectedItems` passed to consumer callbacks are jQuery.
declare const $: any;
declare const Craft: any;

/**
 * ElementActionTrigger — a port of `Craft.ElementActionTrigger` onto
 * `@craftcms/garnish` `Base`. Wires an element-index action button: it
 * enables/disables based on the current selection and runs the action's
 * `activate` handler. Booted by PHP element actions (e.g. ShowInFolder) and by
 * the (still-legacy) element index, so exposed on `window.Craft`.
 */
export class ElementActionTrigger extends Base {
  static defaults: any = {
    type: null,
    bulk: true,
    requireId: true,
    validateSelection: null,
    beforeActivate: async () => {},
    activate: null,
    afterActivate: async () => {},
  };

  declare settings: any;

  elementIndex: any = null;
  maxLevels: any = null;
  newChildUrl: any = null;
  $trigger: any = null;
  $selectedItems: any = null;
  triggerEnabled = true;

  constructor(settings?: any) {
    super();
    if (new.target === ElementActionTrigger) {
      this.init(settings);
    }
  }

  init(settings: any): void {
    // Save a reference to the element index that this trigger will be used with
    this.elementIndex = Craft.currentElementIndex;

    if (!this.elementIndex?.triggers) {
      return;
    }

    // Register the trigger on the element index, so it can be destroyed when the view is updated
    this.elementIndex.triggers.push(this);

    if (!$.isPlainObject(settings)) {
      settings = {};
    }

    // batch => bulk
    if (settings.batch !== undefined) {
      settings.bulk = settings.batch;
      delete settings.batch;
    }
    Object.defineProperty(settings, 'batch', {
      get() {
        return this.bulk;
      },
      set(v) {
        this.bulk = v;
      },
    });

    this.setSettings(settings, ElementActionTrigger.defaults);

    const triggerId = settings.triggerId ?? `${settings.type}-actiontrigger`;
    this.$trigger = $(`#${this.elementIndex.namespaceId(triggerId)}`).data(
      'trigger',
      this
    );

    // Do we have a custom handler?
    if (this.settings.activate) {
      // Prevent the element index's click handler
      this.$trigger.data('custom-handler', true);

      let $button = this.$trigger.find('button,.btn');
      if (!$button.length) {
        $button = this.$trigger;
      }
      this.addListener($button, 'activate', 'handleTriggerActivation');
    }

    this.updateTrigger();
    this.elementIndex.on('selectionChange', this.updateTrigger.bind(this));
  }

  updateTrigger(): void {
    // Ignore if the last element was just unselected
    if (this.elementIndex.getSelectedElements().length === 0) {
      return;
    }

    if (this.validateSelection()) {
      this.enableTrigger();
    } else {
      this.disableTrigger();
    }
  }

  validateSelection(): boolean {
    this.$selectedItems = this.elementIndex.getSelectedElements();

    if (!this.settings.bulk && this.$selectedItems.length > 1) {
      return false;
    }

    if (this.settings.requireId) {
      for (let i = 0; i < this.$selectedItems.length; i++) {
        const $item = this.$selectedItems.eq(i);
        if (!$item[0]?.hasAttribute('data-id') || $item.is('[data-id=""]')) {
          return false;
        }
      }
    }

    if (this.settings.validateSelection instanceof Function) {
      return this._call(() =>
        this.settings.validateSelection(this.$selectedItems, this.elementIndex)
      );
    }

    return true;
  }

  enableTrigger(): void {
    if (this.triggerEnabled) {
      return;
    }

    const $button = this.$trigger.has('button,.btn').length
      ? this.$trigger.find('button,.btn')
      : this.$trigger;
    $button.removeClass('disabled').removeAttr('aria-disabled');
    this.triggerEnabled = true;
  }

  disableTrigger(): void {
    if (!this.triggerEnabled) {
      return;
    }

    const $button = this.$trigger.has('button,.btn').length
      ? this.$trigger.find('button,.btn')
      : this.$trigger;
    $button.addClass('disabled').attr('aria-disabled', 'true');
    this.triggerEnabled = false;
  }

  handleTriggerActivation(): void {
    if (this.triggerEnabled) {
      this._call(() =>
        this.settings.activate(this.$selectedItems, this.elementIndex)
      );
    }
  }

  _call(fn: () => any): any {
    // temporarily set Craft.elementIndex to the trigger's index instance, for BC
    const globalElementIndex = Craft.elementIndex;
    Craft.elementIndex = this.elementIndex;
    const response = fn();
    Craft.elementIndex = globalElementIndex;
    return response;
  }
}
