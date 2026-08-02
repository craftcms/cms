export type FormDefinitionData =
  CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;
export type FormElementData =
  CraftCms.Cms.Cp.FormDefinitions.Data.FormElementData;
export type JsonValue = CraftCms.Cms.Cp.FormDefinitions.Data.JsonValue;

export type FormValues = Record<string, unknown>;
export type FormErrors = Record<string, string | string[]>;

export interface FieldContext {
  inputId: string;
  readOnly: boolean;
  required: boolean;
}

export interface RenderContext {
  bindingScope: string;
  values: FormValues;
  errors: FormErrors;
  readOnly: boolean;
}
