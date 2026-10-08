import Cp from '@/bootstrap/cp';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import FormKitchenSink from './pages/FormKitchenSink.vue';
import Chips from './chips/Chips.vue';
import './layout-slots-demo/register';

Cp.$inertia.register(
  'workbench/FormKitchenSink',
  FormKitchenSink as unknown as InertiaPageComponent
);

Cp.$inertia.register('workbench/Chips', Chips as unknown as InertiaPageComponent);
