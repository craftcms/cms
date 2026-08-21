import {FieldLayoutDesigner} from './field-layout-designer';
import {Tab} from './tab';
import {Element} from './element';
import {CardViewDesigner} from './card-view-designer';
import {BaseDrag, ElementDrag, TabDrag} from './drags';
import '@/modules/grid';
import CraftFieldLayoutDesigner from '@/modules/field-layout-designer/field-layout-designer.ce';
import {defineElement} from '@/common/web-components';
import {registerCraftGlobals} from '@/common/craft-global';

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
registerCraftGlobals({FieldLayoutDesigner});

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
