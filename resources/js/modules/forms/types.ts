type GeneratedFormPayload = CraftCms.Cms.Form.FormPayload;
type GeneratedFormNodePayload = GeneratedFormPayload['nodes'][number];
type GeneratedFormControlPayload = NonNullable<
  GeneratedFormNodePayload['control']
>;

export type FormControlPayload<Props extends object = Record<string, unknown>> =
  Omit<GeneratedFormControlPayload, 'props'> & {props: Props};

export type FormNodePayload<
  Props extends object = Record<string, unknown>,
  ControlProps extends object = Record<string, unknown>,
> = Omit<GeneratedFormNodePayload, 'props' | 'control' | 'children'> & {
  props: Props;
  control?: FormControlPayload<ControlProps>;
  children?: FormNodePayload<Props, ControlProps>[];
};

export type FormPayload<
  ControlProps extends object = Record<string, unknown>,
  NodeProps extends object = Record<string, unknown>,
> = Omit<GeneratedFormPayload, 'nodes'> & {
  nodes: FormNodePayload<NodeProps, ControlProps>[];
};
