import type {OverType as OverTypeInstance} from 'overtype';
import type {PreviewController} from './preview';
import {escapeMarkdownLabel} from './utilities';
import '@craftcms/ui/components/popover/popover';
import '../../link-field/craft-link-field';
import type {LinkFieldValue} from '../../link-field/craft-link-field';

type LinkPopoverOptions = {
  advancedFields: string[];
  showLabelField: boolean;
  types: unknown[];
};

type ElementSelectStartEvent = CustomEvent<{
  restoreFocusTo?: HTMLElement;
  waitUntil: (promise: Promise<unknown>) => void;
}>;

let popoverAnchorIndex = 0;

export type LinkPopoverController = {
  destroy: () => void;
  open: (event?: Event | null) => void;
};

export function createLinkPopoverController(
  editor: OverTypeInstance,
  preview: PreviewController,
  options: LinkPopoverOptions
): LinkPopoverController {
  let popover: HTMLElementTagNameMap['craft-popover'] | null = null;
  let trigger: HTMLElement | null = disclosureTrigger();
  let selectionEnd = 0;
  let selectionStart = 0;
  let resumeFocusTarget: HTMLElement | null = null;
  let suspended = false;

  if (trigger) {
    setDisclosureState(trigger, false);
  }

  function open(event?: Event | null): void {
    event?.preventDefault();
    event?.stopPropagation();
    close();

    selectionStart = editor.textarea.selectionStart;
    selectionEnd = editor.textarea.selectionEnd;

    const content = document.createElement('div');
    content.slot = 'content';
    content.classList.add('p-2');

    // SAFETY: craft-link-field is registered with these public configuration properties.
    const linkField = document.createElement(
      'craft-link-field'
    ) as HTMLElement & {
      advancedFields: string[];
      showLabelField: boolean;
      types: unknown[];
    };
    linkField.advancedFields = options.advancedFields;
    linkField.showLabelField = options.showLabelField;
    linkField.types = options.types;
    content.appendChild(linkField);

    trigger = triggerElement(event);
    const triggerId = anchorId(trigger);

    popover = document.createElement('craft-popover');
    popover.className = 'markdown-link-popover';
    popover.id = popoverId(triggerId);
    popover.placement = 'bottom-start';
    // Lion positions the overlay against its slotted `invoker` by default, but
    // the link button lives outside the popover. Easy enough, we just need to supply
    // it as the overlay's `referenceNode` so Popper anchors to it without the
    // button needing to be a slotted child.
    popover.config = {referenceNode: trigger};
    // Lion's content lives in the `content` slot (rendered inside the pane).
    popover.appendChild(content);
    document.body.appendChild(popover);

    // SAFETY: The registered apply event passes the CustomEvent payload consumed by handleApply.
    linkField.addEventListener('apply', handleApply as EventListener);
    linkField.addEventListener('cancel', handleCancel);
    // SAFETY: The element-select-start listener ignores the event payload.
    linkField.addEventListener(
      'element-select-start',
      suspend as EventListener
    );
    linkField.addEventListener('element-select-end', resume);
    popover.addEventListener('opened-changed', handlePopoverAfterHide);

    setDisclosureState(trigger, false);
    void showPopover(popover);
  }

  function disclosureTrigger(): HTMLElement | null {
    return (
      editor.toolbar?.buttons?.link ??
      editor.container.querySelector<HTMLElement>('[data-button="link"]')
    );
  }

  function triggerElement(event?: Event | null): HTMLElement {
    if (event?.currentTarget instanceof HTMLElement) {
      return event.currentTarget;
    }

    return (
      editor.toolbar?.buttons?.link ??
      editor.container.querySelector<HTMLElement>('[data-button="link"]') ??
      editor.wrapper
    );
  }

  function anchorId(trigger: HTMLElement): string {
    if (!trigger.id) {
      popoverAnchorIndex += 1;
      trigger.id = `markdown-link-popover-anchor-${popoverAnchorIndex}`;
    }

    return trigger.id;
  }

  function handleApply(event: CustomEvent<LinkFieldValue>): void {
    const detail = event.detail;
    const selectedText = editor.textarea.value.slice(
      selectionStart,
      selectionEnd
    );
    const label =
      detail.label || selectedText || detail.defaultLabel || detail.value;
    const destination = markdownDestination(detail.href);
    const markdown = detail.title
      ? `[${escapeMarkdownLabel(label)}](${destination} "${escapeMarkdownTitle(detail.title)}")`
      : `[${escapeMarkdownLabel(label)}](${destination})`;

    editor.textarea.focus();
    editor.textarea.setSelectionRange(selectionStart, selectionEnd);
    editor.textarea.setRangeText(markdown, selectionStart, selectionEnd, 'end');
    editor.textarea.dispatchEvent(new Event('input', {bubbles: true}));

    if (preview.isActive()) {
      void preview.render(editor.getValue());
    }

    close();
  }

  function handleCancel(): void {
    close({restoreFocus: true});
  }

  function suspend(event: ElementSelectStartEvent): void {
    if (!popover) {
      return;
    }

    suspended = true;
    resumeFocusTarget = event.detail.restoreFocusTo ?? null;
    setDisclosureState(trigger, false);
    event.detail.waitUntil(popover.close());
  }

  function resume(): void {
    const currentPopover = popover;

    if (!currentPopover) {
      return;
    }

    suspended = false;
    void showPopover(currentPopover, resumeFocusTarget);
    resumeFocusTarget = null;
  }

  function handlePopoverAfterHide(event: Event): void {
    if (event.target !== popover) {
      return;
    }

    // `opened-changed` fires on both open and close; only react to closes.
    if (event instanceof CustomEvent && event.detail?.opened) {
      return;
    }

    if (suspended) {
      return;
    }

    close();
  }

  function escapeMarkdownTitle(title: string): string {
    return title.replace(/(["\\])/g, '\\$1');
  }

  function markdownDestination(destination: string): string {
    if (isReferenceTag(destination)) {
      return destination;
    }

    return `<${destination.replace(/([\\<>])/g, '\\$1')}>`;
  }

  function isReferenceTag(destination: string): boolean {
    return /^\{[\w\\]+:[^}\s]+\}$/.test(destination);
  }

  function focusFirstControl(): void {
    popover
      ?.querySelector<HTMLElement>(
        'craft-input, craft-select, craft-button, input, select, button'
      )
      ?.focus();
  }

  async function showPopover(
    target: HTMLElementTagNameMap['craft-popover'],
    focusTarget: HTMLElement | null = null
  ): Promise<void> {
    await target.updateComplete;

    if (popover !== target) {
      return;
    }

    await target.open();
    setDisclosureState(trigger, true);

    if (focusTarget?.isConnected) {
      focusTarget.focus();

      return;
    }

    focusFirstControl();
  }

  function close({restoreFocus = false} = {}): void {
    const currentPopover = popover;
    const currentTrigger = trigger;
    popover = null;
    resumeFocusTarget = null;
    suspended = false;

    if (currentTrigger) {
      setDisclosureState(currentTrigger, false);
    }

    currentPopover?.removeEventListener(
      'opened-changed',
      handlePopoverAfterHide
    );
    currentPopover?.remove();

    if (restoreFocus) {
      currentTrigger?.focus();
    }
  }

  function setDisclosureState(
    target: HTMLElement | null,
    expanded: boolean
  ): void {
    if (!target) {
      return;
    }

    target.setAttribute('aria-controls', popoverId(anchorId(target)));
    target.setAttribute('aria-expanded', expanded.toString());
    target.removeAttribute('aria-pressed');
  }

  function popoverId(triggerId: string): string {
    return `${triggerId}-popover`;
  }

  return {
    destroy: close,
    open,
  };
}
