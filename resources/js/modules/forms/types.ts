type GeneratedFormPayload = CraftCms.Cms.Form.FormPayload;
type GeneratedFormNodePayload = GeneratedFormPayload['nodes'][number];
type GeneratedFormControlPayload = NonNullable<
  GeneratedFormNodePayload['control']
>;

export type FormScalar = string | number | boolean | null | undefined | File;
export type FormValue = FormScalar | FormValues | FormValue[];

export interface FormValues {
  [key: string]: FormValue;
}

/** A {@link FormValue} reduced to its comparable form. See `canonical()`. */
export type CanonicalFormValue =
  | string
  | number
  | boolean
  | null
  | undefined
  | CanonicalFormValue[]
  | {[key: string]: CanonicalFormValue};

export type FormPropertyValue =
  | string
  | number
  | boolean
  | null
  | FormProperties
  | FormPropertyValue[];

export interface FormProperties {
  [key: string]: FormPropertyValue;
}

export type FormControlPayload<Props extends object = FormProperties> = Omit<
  GeneratedFormControlPayload,
  'props' | 'forms' | 'reactive'
> & {
  props: Props;
  forms?: NestedFormPayload[];
  reactive?: boolean;
};

export type FormNodePayload<
  Props extends object = FormProperties,
  ControlProps extends object = FormProperties,
> = Omit<GeneratedFormNodePayload, 'props' | 'control' | 'children'> & {
  props: Props;
  control?: FormControlPayload<ControlProps>;
  children?: FormNodePayload<Props, ControlProps>[];
};

export type FormPayload<
  ControlProps extends object = FormProperties,
  NodeProps extends object = FormProperties,
> = Omit<GeneratedFormPayload, 'nodes' | 'values'> & {
  nodes: FormNodePayload<NodeProps, ControlProps>[];
  values: FormValues;
};

/**
 * The value shape of a nested element repeater (Matrix, Addresses).
 *
 * Blocks are keyed by nested element identity. Identities the server rendered are bare
 * UUIDs; ones the browser minted itself carry a `uid:` prefix until the next save adopts
 * them. `NestedFormPayload.scope` always ends in the bare UUID, so a control holding a
 * freshly minted block has to look its form up under both.
 *
 * @see CraftCms\Cms\Form\Controls\Matrix
 */
export type NestedElementValue = {
  entries: {[uid: string]: NestedElementEntryValue};
  sortOrder: string[];
};

export interface NestedElementEntryValue extends FormValues {
  type?: string;
}

/** The `uid:` prefix the browser puts on blocks it minted itself. */
export const NESTED_ELEMENT_UID_PREFIX = 'uid:';

export type NestedFormPayload = {
  scope: string[];
  refreshable: boolean;
  nodes: FormNodePayload[];
};

export type FormChangeKind = 'discrete' | 'typing';

export type FormChange = {
  kind: FormChangeKind;
  path: string[];
  scope?: string[];
  refreshable?: boolean;
};

export type FormControlOverrideProps = {
  control: FormControlPayload;
  value: FormValue;
  values: FormPayload['values'];
  label?: string;
  editable: boolean;
  invalid: boolean;
  required: boolean;
  setValue(value: FormValue, kind?: FormChangeKind): void;
};
