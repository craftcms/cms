import {FieldLayoutDesigner} from './FieldLayoutDesigner';
import {Tab} from './Tab';
import {Element} from './Element';
import {CardViewDesigner} from './CardViewDesigner';
import {BaseDrag, TabDrag, ElementDrag} from './drags';

// Re-expose the sub-classes on the constructor, exactly as the legacy bundle
// did (`Craft.FieldLayoutDesigner.Tab`, `.Element`, `.CardViewDesigner`,
// `.BaseDrag`, `.TabDrag`, `.ElementDrag`).
const FLD = FieldLayoutDesigner as any;
FLD.Tab = Tab;
FLD.Element = Element;
FLD.CardViewDesigner = CardViewDesigner;
FLD.BaseDrag = BaseDrag;
FLD.TabDrag = TabDrag;
FLD.ElementDrag = ElementDrag;

// Assign onto the legacy `Craft` global so the PHP-emitted
// `new Craft.FieldLayoutDesigner("#id", settings)` keeps working unchanged. The
// `Craft` global is created by the legacy cp bundle; we only assign onto it.
const craft = (window as any).Craft ?? ((window as any).Craft = {});
craft.FieldLayoutDesigner = FieldLayoutDesigner;

console.log('hey from the new code');
export {
  FieldLayoutDesigner,
  Tab,
  Element,
  CardViewDesigner,
  BaseDrag,
  TabDrag,
  ElementDrag,
};
