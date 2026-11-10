import type {CpComponentRegistry} from '@/bootstrap/components';
import ActionNode from './ActionNode.vue';
import ActionMenuNode from './ActionMenuNode.vue';
import CopyAttributeNode from './CopyAttributeNode.vue';
import CheckboxControl from './CheckboxControl.vue';
import FieldNode from './FieldNode.vue';
import ChoiceControl from './ChoiceControl.vue';
import ColorControl from './ColorControl.vue';
import ColorSelectControl from './ColorSelectControl.vue';
import ComboboxControl from './ComboboxControl.vue';
import UiRenderer from './UiRenderer.vue';
import GroupNode from './GroupNode.vue';
import ScopeNode from './ScopeNode.vue';
import LightswitchControl from './LightswitchControl.vue';
import MoneyControl from './MoneyControl.vue';
import LinkControl from './LinkControl.vue';
import TextControl from './TextControl.vue';
import TextareaControl from './TextareaControl.vue';
import IconPickerControl from './IconPickerControl.vue';
import ElementSelectControl from './ElementSelectControl.vue';
import FieldSelectControl from './FieldSelectControl.vue';
import EntryTypeSelectControl from './EntryTypeSelectControl.vue';
import ContentBlockControl from './ContentBlockControl.vue';
import DateTimeControl from './DateTimeControl.vue';
import MarkdownContentNode from './MarkdownContentNode.vue';
import MissingProvider from './MissingProvider.vue';
import TabNode from './TabNode.vue';
import TemplateContentNode from './TemplateContentNode.vue';
import CalloutNode from './CalloutNode.vue';
import HeadingNode from './HeadingNode.vue';
import HandleControl from './HandleControl.vue';
import SlugControl from './SlugControl.vue';
import HiddenControl from './HiddenControl.vue';
import HiddenFieldNode from './HiddenFieldNode.vue';
import LineBreakNode from './LineBreakNode.vue';
import SeparatorNode from './SeparatorNode.vue';
import './content-block-input';

/**
 * Controls only some screens render — settings, admin tables, nested
 * elements, Markdown — load on first use rather than with every CP page. The
 * registry wraps their loaders in async components. Module-level, so
 * registering twice hands the registry the same loaders.
 */
const lazyControls = {
  ConditionBuilderControl: () => import('./ConditionBuilderControl.vue'),
  PermissionTreeControl: () => import('./PermissionTreeControl.vue'),
  UserGroupSelectControl: () => import('./UserGroupSelectControl.vue'),
  MarkdownControl: () => import('./MarkdownControl.vue'),
  TableControl: () => import('./TableControl.vue'),
  AdminTableNode: () => import('./AdminTableNode.vue'),
  TableColumnsControl: () => import('./TableColumnsControl.vue'),
  AddressControl: () => import('./AddressControl.vue'),
  ElementSitesControl: () => import('./ElementSitesControl.vue'),
  GroupedEntryTypeManagerControl: () =>
    import('./GroupedEntryTypeManagerControl.vue'),
  FieldLayoutDesignerControl: () => import('./FieldLayoutDesignerControl.vue'),
  NestedElementBlocksControl: () =>
    import('./nested-elements/NestedElementBlocksControl.vue'),
  NestedElementsControl: () =>
    import('./nested-elements/NestedElementsControl.vue'),
};

export function registerUiComponents(
  components: Pick<CpComponentRegistry, 'register'>
): void {
  components.register('craft:ui', UiRenderer);
  components.register('craft:field', FieldNode);
  components.register('craft:action', ActionNode);
  components.register('craft:action-menu', ActionMenuNode);
  components.register('craft:copy-attribute', CopyAttributeNode);
  components.register('craft:group', GroupNode);
  components.register('craft:scope', ScopeNode);
  components.register('craft:tab', TabNode);
  components.register('craft:template-content', TemplateContentNode);
  components.register('craft:markdown-content', MarkdownContentNode);
  components.register('craft:missing-node', MissingProvider);
  components.register('craft:missing-control', MissingProvider);
  components.register('craft:callout', CalloutNode);
  components.register('craft:heading', HeadingNode);
  components.register('craft:line-break', LineBreakNode);
  components.register('craft:separator', SeparatorNode);
  components.register('craft:handle', HandleControl);
  components.register('craft:slug', SlugControl);
  components.register('craft:hidden-field', HiddenFieldNode);
  components.register('craft:hidden', HiddenControl);
  components.register('craft:text', TextControl);
  components.register('craft:combobox', ComboboxControl);
  components.register('craft:textarea', TextareaControl);
  components.register('craft:lightswitch', LightswitchControl);
  components.register('craft:checkbox', CheckboxControl);
  components.register('craft:choice', ChoiceControl);
  components.register(
    'craft:condition-builder',
    lazyControls.ConditionBuilderControl
  );
  components.register('craft:number', TextControl);
  components.register('craft:range', TextControl);
  components.register('craft:date', TextControl);
  components.register('craft:date-time', DateTimeControl);
  components.register('craft:time', TextControl);
  components.register('craft:color', ColorControl);
  components.register('craft:color-select', ColorSelectControl);
  components.register('craft:money', MoneyControl);
  components.register(
    'craft:permission-tree',
    lazyControls.PermissionTreeControl
  );
  components.register(
    'craft:user-group-select',
    lazyControls.UserGroupSelectControl
  );
  components.register('craft:markdown', lazyControls.MarkdownControl);
  components.register('craft:table', lazyControls.TableControl);
  components.register('craft:admin-table', lazyControls.AdminTableNode);
  components.register('craft:table-columns', lazyControls.TableColumnsControl);
  components.register('craft:link', LinkControl);
  components.register('craft:address', lazyControls.AddressControl);
  components.register('craft:icon-picker', IconPickerControl);
  components.register('craft:element-select', ElementSelectControl);
  components.register('craft:element-sites', lazyControls.ElementSitesControl);
  components.register('craft:field-select', FieldSelectControl);
  components.register('craft:entry-type-select', EntryTypeSelectControl);
  components.register(
    'craft:grouped-entry-type-manager',
    lazyControls.GroupedEntryTypeManagerControl
  );
  components.register(
    'craft:field-layout-designer',
    lazyControls.FieldLayoutDesignerControl
  );
  components.register(
    'craft:nested-element-blocks',
    lazyControls.NestedElementBlocksControl
  );
  components.register(
    'craft:nested-elements',
    lazyControls.NestedElementsControl
  );
  components.register('craft:content-block', ContentBlockControl);
}
