import {MatrixInput} from './matrix-input';
import {MatrixEntry} from './matrix-entry';
import {containerMatrixEntries} from './support';
import {registerCraftGlobals} from '@/common/craft-global';

/**
 * Legacy callers construct `new Craft.MatrixInput(id, entryTypes,
 * inputNamePrefix, settings)` and use the statics (`rememberCollapsedEntryId(...)` from PHP flash
 * JS, the collapsed-entry storage helpers). The modern class keeps the same
 * constructor and static surface, so the global is the class itself.
 *
 * `Craft.MatrixInput.Entry` is exposed for parity, with the legacy
 * `(matrix, $container)` jQuery-collection argument unwrapped.
 */
class CraftMatrixEntryGlobal extends MatrixEntry {
  constructor(matrix: MatrixInput, container: HTMLElement | {0?: HTMLElement}) {
    const el =
      container instanceof HTMLElement ? container : (container[0] ?? null);
    if (!el) {
      throw new Error('Craft.MatrixInput.Entry: no container element given.');
    }
    // Legacy code constructed entries repeatedly without a guard; reuse any
    // existing instance's container binding by tearing it down first.
    containerMatrixEntries.get(el)?.destroy();
    super(matrix, el);
  }
}

// Assign onto the legacy `Craft` global so the PHP-emitted
// `new Craft.MatrixInput(...)` and `Craft.MatrixInput.rememberCollapsedEntryId()`
// keep working.
const MatrixInputGlobal = Object.assign(MatrixInput, {
  Entry: CraftMatrixEntryGlobal,
});
registerCraftGlobals({MatrixInput: MatrixInputGlobal});

export {MatrixInput, MatrixEntry};
