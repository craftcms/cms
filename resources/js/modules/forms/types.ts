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
  'props' | 'forms'
> & {
  props: Props;
  forms?: NestedFormPayload[];
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
