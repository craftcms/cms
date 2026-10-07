import Cp from '@/bootstrap/cp';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import FormKitchenSink from './pages/FormKitchenSink.vue';
import './layout-slots-demo/register';

Cp.$inertia.register(
  'workbench/FormKitchenSink',
  FormKitchenSink as unknown as InertiaPageComponent
);
