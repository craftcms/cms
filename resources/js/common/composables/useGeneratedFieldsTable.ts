import {type Ref} from 'vue';

/**
 * Reads the generated-fields table's value back at submit time for Inertia
 * forms, mirroring {@link useFieldLayoutDesigner}.
 *
 * The `<craft-generated-fields-table>` element (rendered inside the field layout
 * designer's markup) self-boots its table and keeps its own distributed named
 * inputs, so native/Twig forms post it directly. Inertia forms, however, only
 * collect the designer's single hidden `fieldLayout` input — so this pulls the
 * table's value out as the nested `generatedFields` object the server expects
 * and lets the submit transform merge it into the payload.
 *
 * @param hostRef - The element that contains the field layout designer markup
 *   (the same host passed to {@link useFieldLayoutDesigner}); the generated
 *   fields table lives within it.
 */
export function useGeneratedFieldsTable(hostRef: Ref<HTMLElement | undefined>) {
  /**
   * Serializes the table's inputs into an ordered list of row payloads in DOM
   * (drag-sort) order — `[{ name, handle, template, uid }, …]` — or `[]` when
   * the table isn't present. The server reads it via
   * `Request::input('generatedFields')` and `array_values()`-es it, so the list
   * order becomes the saved sort order (a keyed object would lose the order; see
   * the element's `serialize()`).
   */
  function serialize(): any[] {
    const el = hostRef.value?.querySelector('craft-generated-fields-table');
    return el?.serialize() ?? [];
  }

  return {serialize};
}
