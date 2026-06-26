import {FieldLayoutDesigner} from './FieldLayoutDesigner';
import {Tab} from './Tab';
import {Element} from './Element';
import {CardViewDesigner} from './CardViewDesigner';
import {BaseDrag, ElementDrag, TabDrag} from './drags';
import CraftFieldLayoutDesigner from '@/modules/field-layout-designer/FieldLayoutDesigner.wc';
import {defineElement} from '@/common/web-components';

// Re-expose the sub-classes on the constructor, as the legacy bundle did
// (`Craft.FieldLayoutDesigner.Tab`, `.Element`, `.CardViewDesigner`, etc.).
const FLD = FieldLayoutDesigner as any;
FLD.Tab = Tab;
FLD.Element = Element;
FLD.CardViewDesigner = CardViewDesigner;
FLD.BaseDrag = BaseDrag;
FLD.TabDrag = TabDrag;
FLD.ElementDrag = ElementDrag;

// Assign onto the legacy `Craft` global (created by the cp bundle) so the
// PHP-emitted `new Craft.FieldLayoutDesigner("#id", settings)` keeps working.
const craft = (window as any).Craft ?? ((window as any).Craft = {});
craft.FieldLayoutDesigner = FieldLayoutDesigner;

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
