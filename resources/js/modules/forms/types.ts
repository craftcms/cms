type GeneratedFormPayload = CraftCms.Cms.Form.FormPayload;
type GeneratedFormNodePayload = GeneratedFormPayload['nodes'][number];
type GeneratedFormControlPayload = NonNullable<
    GeneratedFormNodePayload['control']
>;

export type FormControlPayload<Props extends object = Record<string, unknown>> =
    Omit<GeneratedFormControlPayload, 'props' | 'forms'> & {
        props: Props;
        forms?: NestedFormPayload[];
    };

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
    value: unknown;
    values: FormPayload['values'];
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
    setValue(value: unknown, kind?: FormChangeKind): void;
};
