import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';

type GeneratedUiPayload = CraftCms.Cms.Ui.UiPayload;
type GeneratedUiNodePayload = GeneratedUiPayload['nodes'][number];
type GeneratedUiControlPayload = NonNullable<GeneratedUiNodePayload['control']>;

export type UiScalar = string | number | boolean | null | undefined | File;
export type UiValue = UiScalar | UiValues | UiValue[];

export interface UiValues {
  [key: string]: UiValue;
}

/** A {@link UiValue} reduced to its comparable form. See `canonical()`. */
export type CanonicalUiValue =
  | string
  | number
  | boolean
  | null
  | undefined
  | CanonicalUiValue[]
  | {[key: string]: CanonicalUiValue};

export type UiPropertyValue =
  | string
  | number
  | boolean
  | null
  | UiProperties
  | UiPropertyValue[];

export interface UiProperties {
  [key: string]: UiPropertyValue;
}

export type TextControlProps = {
  inputType?: string;
  min?: number | string;
  max?: number | string;
  step?: number | string;
  maxLength?: number;
  placeholder?: string;
  inputMode?: string;
  autofocus?: boolean;
  autocomplete?: boolean | string;
  autocorrect?: boolean;
  autocapitalize?: boolean;
  size?: number;
  dir?: string;
  monospace?: boolean;
  suffix?: string;
  textExpanderTriggers?: TextExpanderTriggers;
};

/**
 * `emptyValue` stays omitted rather than being retyped: `UiValue` is
 * recursive, and threading another branch of it through the payload puts
 * TypeScript over its instantiation depth wherever the payload is inferred.
 * `controlValueAt()` is the only thing that reads it, and narrows it there.
 */
export type UiControlPayload<Props extends object = UiProperties> = Omit<
  GeneratedUiControlPayload,
  'props' | 'uis' | 'reactive' | 'emptyValue' | 'nestsUis' | 'omitNullValue'
> & {
  props: Props;
  uis?: NestedUiPayload[];
  reactive?: boolean;
  /** Whether the control renders nested uis. Shipped only when true. */
  nestsUis?: boolean;
  /** Omit presentation-only null values from mutations. Shipped only when true. */
  omitNullValue?: boolean;
};

export type UiNodePayload<
  Props extends object = UiProperties,
  ControlProps extends object = UiProperties,
> = Omit<GeneratedUiNodePayload, 'props' | 'control' | 'children'> & {
  props: Props;
  control?: UiControlPayload<ControlProps>;
  children?: UiNodePayload<Props, ControlProps>[];
};

export type UiPayload<
  ControlProps extends object = UiProperties,
  NodeProps extends object = UiProperties,
> = Omit<GeneratedUiPayload, 'nodes' | 'values'> & {
  nodes: UiNodePayload<NodeProps, ControlProps>[];
  values: UiValues;
};

/**
 * The value shape of a nested element repeater (Matrix, Addresses).
 *
 * Blocks are keyed by nested element identity. Identities the server rendered are bare
 * UUIDs; ones the browser minted itself carry a `uid:` prefix until the next save adopts
 * them. `NestedUiPayload.scope` always ends in the bare UUID, so a control holding a
 * freshly minted block has to look its UI up under both.
 *
 * @see CraftCms\Cms\Ui\Controls\NestedElementBlocks
 */
export type NestedElementValue = {
  entries: {[uid: string]: NestedElementEntryValue};
  sortOrder: string[];
};

export interface NestedElementEntryValue extends UiValues {
  type?: string;
}

/** The `uid:` prefix the browser puts on blocks it minted itself. */
export const NESTED_ELEMENT_UID_PREFIX = 'uid:';

export type NestedUiPayload = {
  scope: string[];
  refreshable: boolean;
  nodes: UiNodePayload[];
};

export type UiChangeKind = 'discrete' | 'typing';

export type UiChange = {
  kind: UiChangeKind;
  path: string[];
  scope?: string[];
  refreshable?: boolean;
  /** Updated UI definitions when a control creates nested fields. */
  control?: UiControlPayload<object>;
};

export type UiControlOverrideProps = {
  control: UiControlPayload;
  value: UiValue;
  values: UiPayload['values'];
  errors: UiPayload['errors'];
  label?: string;
  editable: boolean;
  invalid: boolean;
  required: boolean;
  setValue(value: UiValue, kind?: UiChangeKind): void;
};
