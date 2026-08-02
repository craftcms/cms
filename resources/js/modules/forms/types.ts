export type FormPayload = CraftCms.Cms.Cp.Forms.Data.FormPayload;
export type FormElementData = CraftCms.Cms.Cp.Forms.Data.FormElementData;
export type JsonValue = CraftCms.Cms.Cp.Forms.Data.JsonValue;

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
