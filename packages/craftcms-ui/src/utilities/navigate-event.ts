export interface CraftNavigateEventDetail {
  href: string;
}

export type CraftNavigateEvent = CustomEvent<CraftNavigateEventDetail>;

declare global {
  interface HTMLElementEventMap {
    'craft-navigate': CraftNavigateEvent;
  }
}

export function dispatchNavigateEvent(
  host: HTMLElement,
  href: string,
  triggeringClick: MouseEvent
): void {
  const isPlainLeftClick =
    triggeringClick.button === 0 &&
    !triggeringClick.metaKey &&
    !triggeringClick.ctrlKey &&
    !triggeringClick.shiftKey &&
    !triggeringClick.altKey;

  if (!isPlainLeftClick) {
    return;
  }

  const navigateEvent: CraftNavigateEvent = new CustomEvent('craft-navigate', {
    bubbles: true,
    composed: true,
    cancelable: true,
    detail: {href},
  });

  const consumerHandledNavigation = !host.dispatchEvent(navigateEvent);

  if (consumerHandledNavigation) {
    triggeringClick.preventDefault();
  }
}
