import type {CpComponentRegistry} from '@/bootstrap/components';
import ActionNode from './ActionNode.vue';
import CheckboxControl from './CheckboxControl.vue';
import FieldNode from './FieldNode.vue';
import ChoiceControl from './ChoiceControl.vue';
import ConditionBuilderControl from './ConditionBuilderControl.vue';
import ColorControl from './ColorControl.vue';
import ComboboxControl from './ComboboxControl.vue';
import FormRenderer from './FormRenderer.vue';
import GroupNode from './GroupNode.vue';
import LightswitchControl from './LightswitchControl.vue';
import MarkdownControl from './MarkdownControl.vue';
import MoneyControl from './MoneyControl.vue';
import AddressControl from './AddressControl.vue';
import LinkControl from './LinkControl.vue';
import TableControl from './TableControl.vue';
import TextControl from './TextControl.vue';
import TextareaControl from './TextareaControl.vue';
import IconPickerControl from './IconPickerControl.vue';
import ElementSelectControl from './ElementSelectControl.vue';
import GroupedEntryTypeManagerControl from './GroupedEntryTypeManagerControl.vue';
import FieldLayoutDesignerControl from './FieldLayoutDesignerControl.vue';
import FieldSelectControl from './FieldSelectControl.vue';
import MatrixControl from './MatrixControl.vue';
import ContentBlockControl from './ContentBlockControl.vue';
import DateTimeControl from './DateTimeControl.vue';
import MarkdownContentNode from './MarkdownContentNode.vue';
import MissingProvider from './MissingProvider.vue';
import TabNode from './TabNode.vue';
import TemplateContentNode from './TemplateContentNode.vue';
import CalloutNode from './CalloutNode.vue';
import HeadingNode from './HeadingNode.vue';
import HandleControl from './HandleControl.vue';
import HiddenFieldNode from './HiddenFieldNode.vue';
import LineBreakNode from './LineBreakNode.vue';
import PermissionTreeControl from './PermissionTreeControl.vue';
import SeparatorNode from './SeparatorNode.vue';
import './content-block-input';

export function registerFormComponents(
  components: Pick<CpComponentRegistry, 'register'>
): void {
  components.register('craft:form', FormRenderer);
  components.register('craft:field', FieldNode);
  components.register('craft:action', ActionNode);
  components.register('craft:group', GroupNode);
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
  components.register('craft:hidden-field', HiddenFieldNode);
  components.register('craft:text', TextControl);
  components.register('craft:combobox', ComboboxControl);
  components.register('craft:textarea', TextareaControl);
  components.register('craft:lightswitch', LightswitchControl);
  components.register('craft:checkbox', CheckboxControl);
  components.register('craft:choice', ChoiceControl);
  components.register('craft:condition-builder', ConditionBuilderControl);
  components.register('craft:number', TextControl);
  components.register('craft:range', TextControl);
  components.register('craft:date', TextControl);
  components.register('craft:date-time', DateTimeControl);
  components.register('craft:time', TextControl);
  components.register('craft:color', ColorControl);
  components.register('craft:money', MoneyControl);
  components.register('craft:permission-tree', PermissionTreeControl);
  components.register('craft:markdown', MarkdownControl);
  components.register('craft:table', TableControl);
  components.register('craft:link', LinkControl);
  components.register('craft:address', AddressControl);
  components.register('craft:icon-picker', IconPickerControl);
  components.register('craft:element-select', ElementSelectControl);
  components.register('craft:field-select', FieldSelectControl);
  components.register(
    'craft:grouped-entry-type-manager',
    GroupedEntryTypeManagerControl
  );
  components.register(
    'craft:field-layout-designer',
    FieldLayoutDesignerControl
  );
  components.register('craft:matrix', MatrixControl);
  components.register('craft:content-block', ContentBlockControl);
}
