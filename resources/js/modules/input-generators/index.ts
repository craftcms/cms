import {BaseInputGenerator} from './base-input-generator';
import {HandleGenerator} from './handle-generator';
import {SlugGenerator} from './slug-generator';
import {UriFormatGenerator} from './uri-format-generator';
import {DynamicGenerator} from './dynamic-generator';

// Assign onto the legacy `Craft` global so Twig-emitted and plugin
// `new Craft.HandleGenerator('#name', '#handle')` boots — and the modern
// editable-table / generated-fields consumers — keep working. These are plain
// modern ES classes (no `compatify`): nothing in core subclasses them via legacy
// `.extend()`, and plugins that need custom generation use the callback-based
// `Craft.DynamicGenerator`.
const craft = (window as any).Craft ?? ((window as any).Craft = {});
craft.BaseInputGenerator = BaseInputGenerator;
craft.HandleGenerator = HandleGenerator;
craft.SlugGenerator = SlugGenerator;
craft.UriFormatGenerator = UriFormatGenerator;
craft.DynamicGenerator = DynamicGenerator;

export {
  BaseInputGenerator,
  HandleGenerator,
  SlugGenerator,
  UriFormatGenerator,
  DynamicGenerator,
};
