import {Group, GroupedEntryTypeManager} from './grouped-entry-type-manager';
import CraftEntryTypeManager from '@/modules/grouped-entry-type-manager/grouped-entry-type-manager.ce';
import {defineElement} from '@/common/web-components';
import './grouped-entry-type-manager.css';

defineElement('craft-entry-type-manager', CraftEntryTypeManager);

export {GroupedEntryTypeManager, Group, CraftEntryTypeManager};
