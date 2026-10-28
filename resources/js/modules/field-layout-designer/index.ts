import './globals';
import {FieldLayoutDesigner} from './field-layout-designer';
import {Tab} from './tab';
import {Element} from './element';
import {CardViewDesigner} from './card-view-designer';
import {BaseDrag, ElementDrag, TabDrag} from './drags';
import '@/modules/grid';
import CraftFieldLayoutDesigner from '@/modules/field-layout-designer/field-layout-designer.ce';
import {defineElement} from '@/common/web-components';

defineElement('craft-field-layout-designer', CraftFieldLayoutDesigner);

export {
  FieldLayoutDesigner,
  Tab,
  Element,
  CardViewDesigner,
  BaseDrag,
  TabDrag,
  ElementDrag,
  CraftFieldLayoutDesigner,
};
