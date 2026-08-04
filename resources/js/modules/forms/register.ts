import type {CpComponentRegistry} from '@/bootstrap/components';
import FieldNode from './FieldNode.vue';
import ChoiceControl from './ChoiceControl.vue';
import ColorControl from './ColorControl.vue';
import FormRenderer from './FormRenderer.vue';
import GroupNode from './GroupNode.vue';
import LightswitchControl from './LightswitchControl.vue';
import MoneyControl from './MoneyControl.vue';
import ScalarControl from './ScalarControl.vue';
import SelectControl from './SelectControl.vue';
import TextControl from './TextControl.vue';
import TextareaControl from './TextareaControl.vue';

export function registerFormComponents(
  components: Pick<CpComponentRegistry, 'register'>
): void {
  components.register('craft:form', FormRenderer);
  components.register('craft:field', FieldNode);
  components.register('craft:group', GroupNode);
  components.register('craft:text', TextControl);
  components.register('craft:textarea', TextareaControl);
  components.register('craft:select', SelectControl);
  components.register('craft:lightswitch', LightswitchControl);
  components.register('craft:choice', ChoiceControl);
  components.register('craft:number', ScalarControl);
  components.register('craft:range', ScalarControl);
  components.register('craft:date', ScalarControl);
  components.register('craft:time', ScalarControl);
  components.register('craft:color', ColorControl);
  components.register('craft:money', MoneyControl);
}
