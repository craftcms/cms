import Cp from '@/bootstrap/cp';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import UiKitchenSink from './pages/UiKitchenSink.vue';
import './layout-slots-demo/register';

Cp.$inertia.register(
  'workbench/UiKitchenSink',
  UiKitchenSink as unknown as InertiaPageComponent
);
