import type {OverType as OverTypeInstance} from 'overtype';
import type {PreviewController} from './preview';
import {escapeMarkdownLabel} from './utilities';
import '@craftcms/cp/components/popover/popover.ts.mjs';
import '../../link-field/CraftLinkField';
import type {LinkFieldValue} from '../../link-field/CraftLinkField';

type LinkPopoverOptions = {
  advancedFields: string[];
  showLabelField: boolean;
  types: unknown[];
};

type ElementSelectStartEvent = CustomEvent<{
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
  let selectionEnd = 0;
  let selectionStart = 0;
  let suspended = false;

  function open(event?: Event | null): void {
    event?.preventDefault();
    event?.stopPropagation();
    close();

    selectionStart = editor.textarea.selectionStart;
    selectionEnd = editor.textarea.selectionEnd;

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

    const trigger = triggerElement(event);

    popover = document.createElement('craft-popover');
    popover.className = 'markdown-link-popover';
    popover.distance = 6;
    popover.for = anchorId(trigger);
    // `for` resolves after the first update; set the anchor directly so
    // programmatic show() has a target during the first render.
    popover.anchor = trigger;
    popover.placement = 'bottom-start';
    popover.withoutArrow = true;
    popover.appendChild(linkField);
    document.body.appendChild(popover);

    linkField.addEventListener('apply', handleApply as EventListener);
    linkField.addEventListener('cancel', close);
    linkField.addEventListener(
      'element-select-start',
      suspend as EventListener
    );
    linkField.addEventListener('element-select-end', resume);
    popover.addEventListener('wa-after-hide', handlePopoverAfterHide);

    void showPopover(popover);
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
    const destination = `${detail.value}${detail.urlSuffix}`;
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

  function suspend(event: ElementSelectStartEvent): void {
    if (!popover) {
      return;
    }

    suspended = true;
    event.detail.waitUntil(popover.hide() ?? Promise.resolve());
  }

  function resume(): void {
    const currentPopover = popover;

    if (!currentPopover) {
      return;
    }

    suspended = false;
    void showPopover(currentPopover);
  }

  function handlePopoverAfterHide(event: Event): void {
    if (event.target !== popover) {
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

  function focusFirstControl(): void {
    popover
      ?.querySelector<HTMLElement>(
        'craft-input, craft-select, craft-button, input, select, button'
      )
      ?.focus();
  }

  async function showPopover(
    target: HTMLElementTagNameMap['craft-popover']
  ): Promise<void> {
    await target.updateComplete;

    if (popover !== target) {
      return;
    }

    await target.show();
    focusFirstControl();
  }

  function close(): void {
    const currentPopover = popover;
    popover = null;
    suspended = false;

    currentPopover?.removeEventListener(
      'wa-after-hide',
      handlePopoverAfterHide
    );
    currentPopover?.remove();
  }

  return {
    destroy: close,
    open,
  };
}
