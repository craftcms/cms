import {FieldLayoutDesigner} from './field-layout-designer';
import {Tab} from './tab';
import {Element} from './element';
import {CardViewDesigner} from './card-view-designer';
import {BaseDrag, ElementDrag, TabDrag} from './drags';
import '@/modules/grid';
import {registerCraftGlobals} from '@/common/craft-global';

// Re-expose the sub-classes on the constructor, as the legacy bundle did
// (`Craft.FieldLayoutDesigner.Tab`, `.Element`, `.CardViewDesigner`, etc.).
Object.assign(FieldLayoutDesigner, {
  Tab,
  Element,
  CardViewDesigner,
  BaseDrag,
  TabDrag,
  ElementDrag,
});

// Assign onto the legacy `Craft` global (created by the cp bundle) so the
// PHP-emitted `new Craft.FieldLayoutDesigner("#id", settings)` keeps working.
registerCraftGlobals({FieldLayoutDesigner});
