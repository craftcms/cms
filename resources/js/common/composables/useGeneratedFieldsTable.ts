import {type Ref} from 'vue';

/**
 * Reads the generated-fields table's value back at submit time for Inertia forms,
 * mirroring {@link useFieldLayoutDesigner}.
 *
 * The `<craft-generated-fields-table>` element self-boots and keeps its own named
 * inputs, so native/Twig forms post it directly. Inertia forms collect only the
 * designer's single hidden `fieldLayout` input, so this pulls the table's value out
 * for the submit transform to merge in.
 *
 * @param hostRef - The element containing the FLD markup (same host as
 *   {@link useFieldLayoutDesigner}); the generated fields table lives within it.
 */
export function useGeneratedFieldsTable(hostRef: Ref<HTMLElement | undefined>) {
  /**
   * Serializes the table's inputs into an ordered list of row payloads in DOM
   * (drag-sort) order, or `[]` when the table isn't present. The server
   * `array_values()`-es it, so list order becomes the saved sort order (a keyed
   * object would lose it; see the element's `serialize()`).
   */
  function serialize(): any[] {
    const el = hostRef.value?.querySelector('craft-generated-fields-table');
    return el?.serialize() ?? [];
  }

  return {serialize};
}
