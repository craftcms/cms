import type {UiNodePayload} from '../types';

export function rowFields(nodes: UiNodePayload[]): UiNodePayload[] {
  return nodes.flatMap((node) =>
    node.control ? [node] : rowFields(node.children ?? [])
  );
}
