/**
 * The Layout Slots Demo "plugin". Uses only what a third-party plugin gets:
 * `@craftcms/cp` and components resolved by name.
 */
import {registerComponent, registerPage} from '@craftcms/cp';
import LayoutSlotsDemo from './LayoutSlotsDemo.vue';
import LayoutSlotsDemoWidget from './LayoutSlotsDemoWidget.vue';

registerPage('workbench/LayoutSlotsDemo', LayoutSlotsDemo);
registerComponent('workbench:layout-slots-demo-widget', LayoutSlotsDemoWidget);
