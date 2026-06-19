import type {GarnishBaseSettings} from '@craftcms/garnish';

/**
 * Settings accepted by {@link FieldLayoutDesigner}. Mirrors the `$jsSettings`
 * JSON emitted by `src/Cp/FieldLayoutDesigner/FieldLayoutDesigner.php`.
 */
export interface FieldLayoutDesignerSettings extends GarnishBaseSettings {
  elementType: string | null;
  customizableTabs: boolean;
  customizableUi: boolean;
  withCardViewDesigner: boolean;
  alwaysShowThumbAlignmentBtns: boolean;
  readOnly: boolean;
}

/**
 * The serialized field-layout config that round-trips through the hidden
 * `input[data-config-input]`. Very dynamic on the PHP side, so kept loose.
 */
export interface FieldLayoutConfig {
  uid?: string;
  tabs: any[];
  [key: string]: any;
}
