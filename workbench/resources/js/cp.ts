import Cp from '@/bootstrap/cp';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import UiKitchenSink from './pages/UiKitchenSink.vue';
import Chips from './chips/Chips.vue';
import Headings from './headings/Headings.vue';
import './layout-slots-demo/register';
import './plugin-store/releasedCraftApi';
import './assistant-demo/register';

Cp.$inertia.register(
  'workbench/UiKitchenSink',
  UiKitchenSink as unknown as InertiaPageComponent
);

Cp.$inertia.register(
  'workbench/Chips',
  Chips as unknown as InertiaPageComponent
);

Cp.$inertia.register(
  'workbench/Headings',
  Headings as unknown as InertiaPageComponent
);
