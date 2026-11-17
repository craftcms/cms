import './globals';
import {Listbox} from './listbox';
import CraftListbox from '@/modules/listbox/listbox.ce';
import {defineElement} from '@/common/web-components';

defineElement('craft-listbox', CraftListbox);

export {Listbox, CraftListbox};
