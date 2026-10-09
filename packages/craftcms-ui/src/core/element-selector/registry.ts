import {ElementSelectorController} from './element-selector-controller.js';
import type {ElementSelectorOptions} from './types.js';

export type ElementSelectorControllerClass = new (
  options: ElementSelectorOptions
) => ElementSelectorController<any>;

const controllers = new Map<string, ElementSelectorControllerClass>();

/**
 * Registers the controller to use for an element type.
 *
 * Throws on a second registration for the same type, as the legacy registry did
 * — two plugins claiming one element type is a conflict worth surfacing, not one
 * to resolve by last-write-wins.
 */
export function registerElementSelectorController(
  elementType: string,
  controllerClass: ElementSelectorControllerClass
): void {
  if (controllers.has(elementType)) {
    throw new Error(
      `An element selector controller has already been registered for the element type “${elementType}”.`
    );
  }

  controllers.set(elementType, controllerClass);
}

export function hasElementSelectorController(elementType: string): boolean {
  return controllers.has(elementType);
}

/** The controller registered for an element type, or the base controller. */
export function elementSelectorControllerClass(
  elementType: string
): ElementSelectorControllerClass {
  return controllers.get(elementType) ?? ElementSelectorController;
}

export function createElementSelectorController(
  options: ElementSelectorOptions
): ElementSelectorController<any> {
  return new (elementSelectorControllerClass(options.elementType))(options);
}

/**
 * Takes over anything sitting on the legacy `Craft._elementSelectorModalClasses`.
 *
 * The legacy bundle is a plain script that runs before this module, so a plugin
 * can have registered before there was anywhere modern to put it. Existing
 * modern entries win — a type registered here already is left alone.
 *
 * The `window` guard keeps this file importable under the `node` test
 * environment, which is what enforces that the rest of `core/` stays DOM-free.
 */
export function adoptLegacyRegistrations(): void {
  if (typeof window === 'undefined') {
    return;
  }

  const legacy = (window as any).Craft?._elementSelectorModalClasses as
    | Record<string, ElementSelectorControllerClass>
    | undefined;

  if (!legacy) {
    return;
  }

  for (const [elementType, controllerClass] of Object.entries(legacy)) {
    if (!controllers.has(elementType)) {
      controllers.set(elementType, controllerClass);
    }
  }
}

/** Test seam. */
export function resetElementSelectorControllers(): void {
  controllers.clear();
}
