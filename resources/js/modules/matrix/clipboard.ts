/** Element info entries produced by the copy/paste clipboard. */
export interface CopiedElementInfo {
  type: string;
  id: ElementId;
  draftId?: JsonValue;
  revisionId?: JsonValue;
  fieldId?: number | null;
  ownerId?: ElementId;
  siteId?: number | null;
  data?: Record<string, JsonValue | undefined> & {entryTypeId?: number};
}

type ElementId = string | number | null;

type JsonValue =
  | string
  | number
  | boolean
  | null
  | JsonValue[]
  | {[key: string]: JsonValue};

export interface PasteElementParams {
  primaryOwnerId: ElementId;
  ownerId: ElementId;
  fieldId: number | null;
  siteId: number | null;
}

export interface ClipboardRuntime {
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
}

export function craft(): typeof Craft & ClipboardRuntime {
  return Craft as typeof Craft & ClipboardRuntime;
}
