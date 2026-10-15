export interface ElementIndexItem {
  id: string | number;
}

/**
 * Type-specific behavior layered onto the generic element-index renderers.
 * Returning `true` from an event handler tells the renderer not to apply its
 * default selection behavior.
 */
export interface ElementIndexItemBehavior<
  Item extends ElementIndexItem = ElementIndexItem,
> {
  attrs?: (item: Item) => Record<string, unknown> | undefined;
  onClick?: (item: Item, event: MouseEvent) => boolean;
  /**
   * Returning `true` also marks the event handled (`preventDefault()`), so the
   * index's own double-click handling, such as opening an editor, stands down.
   */
  onDblClick?: (item: Item, event: MouseEvent) => boolean;
  onKeydown?: (item: Item, event: KeyboardEvent) => boolean;
}

/** Whether a bubbled key event belongs to a control inside the index item. */
export function isInteractiveItemEvent(event: KeyboardEvent): boolean {
  return event.target !== event.currentTarget;
}
