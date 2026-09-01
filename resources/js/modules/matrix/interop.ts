/**
 * Typed seams to the legacy runtime that the Matrix input still cooperates
 * with. Matrix fields render on legacy-stack pages, where the webpack bundles
 * own several widgets this module must reuse rather than re-implement:
 *
 * - `Garnish.Select` (multi-select), `Craft.Tabs`, and `Craft.ElementEditor`
 *   have no jQuery-free ports yet, so instances are
 *   created/read through the page globals.
 * - Server-rendered action menus are initialized by the legacy bundle, which
 *   stores the `Garnish.DisclosureMenu` instance in jQuery data.
 *
 * Everything here narrows those widgets to the members this module actually
 * uses. As each one gets its own modern port, its interop type should die.
 */

import {jq} from '@/common/utils/jquery';
import type {MatrixEntry} from './matrix-entry';
import type {MatrixInput} from './matrix-input';

/**
 * Reads a legacy jQuery-data value from an element (`$(el).data(key)`), where
 * the legacy bundle stores widget instances.
 */
export function jqData(
  el: Element,
  key: 'disclosureMenu'
): LegacyDisclosureMenu | null;
export function jqData(
  el: Element,
  key: 'elementEditor'
): LegacyElementEditor | null;
export function jqData(
  el: Element,
  key: 'disclosureMenu' | 'elementEditor'
): LegacyDisclosureMenu | LegacyElementEditor | null {
  return jq()?.(el).data(key) ?? null;
}

/**
 * Stores a value in an element's legacy jQuery data, so PHP-emitted snippets
 * and legacy code that read `$(el).data(key)` keep working.
 */
export function setJqData(
  el: Element,
  key: 'entry' | 'matrix',
  value: MatrixEntry | MatrixInput | null
): void {
  jq()?.(el).data(key, value);
}

/** The legacy `Garnish.Select` surface used by {@link MatrixInput}. */
export interface LegacySelect {
  totalSelected: number;
  $selectedItems: {length: number; eq(i: number): HTMLElement};
  getSelectedItems(): ArrayLike<HTMLElement>;
  isSelected(item: HTMLElement): boolean;
  addItems(items: HTMLElement | HTMLElement[]): void;
  resetItemOrder(): void;
  destroy(): void;
}

/** The legacy `Garnish.DisclosureMenu` surface used by {@link MatrixEntry}. */
export interface LegacyDisclosureMenu {
  $container: JQuery<HTMLElement>;
  on(events: string, handler: () => void): void;
  show(): void;
  hide(): void;
  showItem(item: HTMLElement): void;
  hideItem(item: HTMLElement): void;
  destroy(): void;
}

/** The legacy `Craft.ElementEditor` surface used by this module. */
export interface LegacyElementEditor {
  queue?: {push(job: () => Promise<void>): Promise<void>};
  submittingForm?: boolean;
  on(events: string, handler: () => void): void;
  getDraftElementId(id: ElementId): ElementId;
  getDraftElementUid(uid: string | undefined): string | undefined;
  setFormValue(name: string, value: string): Promise<void>;
  pause(): Promise<void>;
  resume(): void | Promise<void>;
  handleDismissibleTips?(): void;
}

/**
 * Legacy runtime constructors/utilities reached through the page globals.
 * These members aren't part of the typed `CraftStatic` surface (yet) — narrow
 * them here instead of sprinkling casts around the module.
 */
export interface LegacyCraftRuntime {
  queue: {push(job: () => Promise<void>): Promise<void>};
  cp: {
    announce(message: string): void;
    displayError(message?: string): void;
    copyElements(elementInfo: CopiedElementInfo[]): void;
    getCopiedElements(): CopiedElementInfo[];
    onCopyElements(
      callback: (elementInfo: CopiedElementInfo[], buttonLabel?: string) => void
    ): void;
    pasteElements(params: PasteElementParams): Promise<{id: number}[]>;
  };
  elementTypeNames: Record<string, string[]>;
  getText(value: string): string;
  filterArray(arr: string[]): string[];
  hasMousePointerEvents(): boolean;
  appendHeadHtml(html: string): Promise<void>;
  appendBodyHtml(html: string): Promise<void>;
  formatInputId(name: string): string;
  namespaceInputName(name: string, namespace?: string): string;
  namespaceId(id: string, namespace?: string): string;
  systemUid: string;
}

/** Element info entries produced by the copy/paste clipboard. */
export interface CopiedElementInfo {
  type: string;
  id: ElementId;
  draftId?: JsonValue;
  revisionId?: JsonValue;
  fieldId?: number | null;
  ownerId?: ElementId;
  siteId?: number | null;
  data?: {entryTypeId?: number};
}

type ElementId = string | number | null;

type JsonValue =
  | string
  | number
  | boolean
  | null
  | JsonValue[]
  | {[key: string]: JsonValue};

interface PasteElementParams {
  primaryOwnerId: ElementId;
  ownerId: ElementId;
  fieldId: number | null;
  siteId: number | null;
}

interface LegacySelectSettings {
  multi: boolean;
  vertical: boolean;
  handle: string;
  filter(target: HTMLElement): boolean;
  checkboxMode: boolean;
}

interface LegacyGarnishRuntime {
  Select: new (
    container: HTMLElement,
    items: HTMLElement[],
    settings: LegacySelectSettings
  ) => LegacySelect;
  DisclosureMenu: new (trigger: HTMLElement) => LegacyDisclosureMenu;
}

/** The ambient `Craft` global, widened with the legacy runtime members. */
export function craft(): typeof Craft & LegacyCraftRuntime {
  // SAFETY: the legacy CP bundle installs these members on the Craft global.
  return Craft as typeof Craft & LegacyCraftRuntime;
}

/** The legacy `Garnish` global (for widgets without modern ports). */
export function legacyGarnish(): LegacyGarnishRuntime {
  // SAFETY: Matrix is initialized only after the legacy Garnish bundle loads.
  return Garnish as LegacyGarnishRuntime;
}
