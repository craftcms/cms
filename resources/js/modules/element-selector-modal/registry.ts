import {BaseElementSelectorModal} from './base-element-selector-modal';

export type ElementSelectorModalClass = new (
  elementType: string,
  settings?: Record<string, any>
) => BaseElementSelectorModal;

const classes = new Map<string, ElementSelectorModalClass>();

/**
 * Registers the modal class to use for an element type.
 *
 * Throws on a second registration for the same type, matching the legacy
 * registry — two plugins claiming one element type is a conflict to surface,
 * not to resolve by last-write-wins.
 */
export function registerElementSelectorModalClass(
  elementType: string,
  modalClass: ElementSelectorModalClass
): void {
  if (classes.has(elementType)) {
    throw new Error(
      `An element selector modal class has already been registered for the element type “${elementType}”.`
    );
  }

  classes.set(elementType, modalClass);
}

export function hasElementSelectorModalClass(elementType: string): boolean {
  return classes.has(elementType);
}

/** The class registered for an element type, or the base modal. */
export function elementSelectorModalClass(
  elementType: string
): ElementSelectorModalClass {
  return classes.get(elementType) ?? BaseElementSelectorModal;
}

export function createElementSelectorModal(
  elementType: string,
  settings?: Record<string, any>
): BaseElementSelectorModal {
  return new (elementSelectorModalClass(elementType))(elementType, settings);
}

/**
 * Takes over anything already on the legacy `Craft._elementSelectorModalClasses`
 * object.
 *
 * The legacy bundle is a plain script and runs before this module, so a plugin
 * can have registered before there was anywhere modern to put it. Existing
 * entries win over nothing here — a type this module registers itself is left
 * alone.
 */
export function adoptLegacyRegistrations(): void {
  const legacy = (window as any).Craft?._elementSelectorModalClasses as
    | Record<string, ElementSelectorModalClass>
    | undefined;

  if (!legacy) {
    return;
  }

  for (const [elementType, modalClass] of Object.entries(legacy)) {
    if (!classes.has(elementType)) {
      classes.set(elementType, modalClass);
    }
  }
}

/** Test seam. */
export function resetElementSelectorModalClasses(): void {
  classes.clear();
}
