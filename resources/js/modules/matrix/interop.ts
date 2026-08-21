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

import {jq as jqGlobal} from '@/common/utils/jquery';

/** The page's jQuery, when the legacy bundle has loaded. */
type JQueryLike = (input: unknown) => {
    data(key: string): unknown;
    data(key: string, value: unknown): unknown;
};

function jq(): JQueryLike | null {
    // Centralized jQuery seam; narrowed to the members Matrix interop uses.
    return jqGlobal() as JQueryLike | null;
}

/**
 * Reads a legacy jQuery-data value from an element (`$(el).data(key)`), where
 * the legacy bundle stores widget instances.
 */
export function jqData(el: Element, key: string): unknown {
    return jq()?.(el).data(key);
}

/**
 * Stores a value in an element's legacy jQuery data, so PHP-emitted snippets
 * and legacy code that read `$(el).data(key)` keep working.
 */
export function setJqData(el: Element, key: string, value: unknown): void {
    jq()?.(el).data(key, value);
}

/** The legacy `Garnish.Select` surface used by {@link MatrixInput}. */
export interface LegacySelect {
    totalSelected: number;
    $selectedItems: {length: number; eq(i: number): unknown};
    getSelectedItems(): ArrayLike<HTMLElement>;
    isSelected(item: unknown): boolean;
    addItems(items: unknown): void;
    resetItemOrder(): void;
    destroy(): void;
}

/** The legacy `Garnish.DisclosureMenu` surface used by {@link MatrixEntry}. */
export interface LegacyDisclosureMenu {
    $container: unknown;
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
    getDraftElementId(id: unknown): unknown;
    getDraftElementUid(uid: unknown): unknown;
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
        copyElements(elementInfo: unknown[]): void;
        getCopiedElements(): CopiedElementInfo[];
        onCopyElements(
            callback: (
                elementInfo: CopiedElementInfo[],
                buttonLabel?: string
            ) => void
        ): void;
        pasteElements(params: Record<string, unknown>): Promise<{id: number}[]>;
    };
    elementTypeNames: Record<string, string[]>;
    getText(value: unknown): unknown;
    filterArray(arr: unknown[]): string[];
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
    id: unknown;
    draftId?: unknown;
    revisionId?: unknown;
    fieldId?: unknown;
    ownerId?: unknown;
    siteId?: unknown;
    data?: {entryTypeId?: number};
}

/** The ambient `Craft` global, widened with the legacy runtime members. */
export function craft(): typeof Craft & LegacyCraftRuntime {
    return Craft as typeof Craft & LegacyCraftRuntime;
}

/** The legacy `Garnish` global (for widgets without modern ports). */
export function legacyGarnish(): {
    Select: new (
        container: unknown,
        items: unknown,
        settings: Record<string, unknown>
    ) => LegacySelect;
    DisclosureMenu: new (trigger: unknown) => LegacyDisclosureMenu;
} {
    return (window as unknown as Record<string, unknown>).Garnish as ReturnType<
        typeof legacyGarnish
    >;
}
