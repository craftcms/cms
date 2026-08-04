import type {CpComponentRegistry} from '@/bootstrap/components';
import FieldNode from './FieldNode.vue';
import FormRenderer from './FormRenderer.vue';
import GroupNode from './GroupNode.vue';
import LightswitchControl from './LightswitchControl.vue';
import SelectControl from './SelectControl.vue';
import TextControl from './TextControl.vue';

export function registerFormComponents(
  components: Pick<CpComponentRegistry, 'register'>
): void {
  components.register('craft:form', FormRenderer);
  components.register('craft:field', FieldNode);
  components.register('craft:group', GroupNode);
  components.register('craft:text', TextControl);
  components.register('craft:select', SelectControl);
  components.register('craft:lightswitch', LightswitchControl);
}
