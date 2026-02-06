import './css/cp-bridge.scss';
import * as cpBridge from '@craftcms/cp/bridge';

window.Craft = window.Craft || {};
Object.assign(window.Craft, cpBridge);
