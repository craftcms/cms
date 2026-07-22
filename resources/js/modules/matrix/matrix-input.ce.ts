import {MatrixInput, type MatrixEntryType} from './matrix-input';
import {ControllerElement} from '@/common/web-components';

/**
 * `<craft-matrix-input>` — boots a {@link MatrixInput} around the
 * server-rendered `.matrix-field` it wraps, so PHP/Twig can emit the element
 * instead of a manual `new Craft.MatrixInput(...)` boot script.
 *
 * Configuration comes from attributes:
 *
 * - `entry-types` — JSON array of `{id, handle, name}` descriptors
 * - `input-name-prefix` — the field's namespaced input name
 * - `settings` — JSON {@link MatrixInputSettings}
 *
 * The wrapped `.matrix-field` keeps its own `id`, which is what the
 * {@link MatrixInput} constructor resolves.
 */
export default class CraftMatrixInput extends ControllerElement<MatrixInput> {
  protected readonly rootSelector = '.matrix-field';

  protected create(root: HTMLElement): MatrixInput {
    const entryTypes = JSON.parse(
      this.getAttribute('entry-types') ?? '[]'
    ) as MatrixEntryType[];
    const settings = JSON.parse(this.getAttribute('settings') ?? '{}');

    return new MatrixInput(
      root.id,
      entryTypes,
      this.getAttribute('input-name-prefix') ?? '',
      settings
    );
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-matrix-input': CraftMatrixInput;
  }
}
