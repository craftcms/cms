import '@craftcms/ui';
import '../../packages/craftcms-legacy/cp/src/js/UI.js';
import Cp from './bootstrap/cp.js';
import {defineEntryFieldLayoutFormHost} from './modules/forms/entry-field-layout-form-host';
import {defineLayoutComponentSettingsFormHost} from './modules/forms/layout-component-settings-form-host';
import './modules/navigation/components/cp-global-sidebar.js';
import './modules/navigation/components/cp-queue-indicator.js';

/**
 * Legacy ports, assigns window.Craft.* so PHP-emitted code still works
 * someday these will be removed and the classes will be used directly
 */
import './modules/sortable-checkbox-select';
import './modules/listbox';
import './modules/matrix';
import './modules/auth/elevated-session';
import './modules/field-layout-designer';
import './modules/editable-table';
import './modules/generated-fields';
import './modules/component-select';
import './modules/input-generators';
import './modules/icon-picker';
import './modules/field-toggle';
import './modules/grouped-entry-type-manager';
import './modules/queue';
import './modules/slideout';
import './modules/auth-method-setup';
import './modules/proxy-scrollbar';
import './modules/element-label';
import './modules/form-observer';
import './modules/interval-manager';
import './modules/entry-mover';
import './modules/prompt-handler';
import './modules/link-field';
import './modules/cp-modal';
import './modules/tabs';
import './modules/grid';
import './modules/chart';
import './modules/data-table-sorter';
import './modules/element-action-trigger';
import './modules/element-thumb-loader';
import './modules/element-table-sorter';
import './modules/asset-mover';
import './modules/element-selector-modal';
import './modules/element-select-input';
import './modules/preview-file-modal';
import './modules/asset-select-input';
import './modules/element-deletion-manager';
import './modules/uploader';
import './modules/nested-element-manager';
import './modules/ui';

window.Cp = Cp as unknown as typeof window.Cp;
defineEntryFieldLayoutFormHost(Cp.$components);
defineLayoutComponentSettingsFormHost(Cp.$components);
