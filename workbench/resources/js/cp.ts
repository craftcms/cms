import {registerPage} from '@craftcms/cp';
import UiKitchenSink from './pages/UiKitchenSink.vue';
import Chips from './chips/Chips.vue';
import './layout-slots-demo/register';
import './plugin-store/releasedCraftApi';
import './assistant-demo/register';

registerPage('workbench/UiKitchenSink', UiKitchenSink);
registerPage('workbench/Chips', Chips);
