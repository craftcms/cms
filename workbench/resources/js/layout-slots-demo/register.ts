/**
 * The Layout Slots Demo "plugin". Uses only what a third-party plugin gets:
 * the global `Cp` object and components resolved by name.
 */
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import LayoutSlotsDemo from './LayoutSlotsDemo.vue';
import LayoutSlotsDemoWidget from './LayoutSlotsDemoWidget.vue';

window.Cp.booting((cp) => {
  cp.$inertia.register(
    'workbench/LayoutSlotsDemo',
    LayoutSlotsDemo as unknown as InertiaPageComponent
  );
  cp.$components.register(
    'workbench:layout-slots-demo-widget',
    LayoutSlotsDemoWidget
  );
});
