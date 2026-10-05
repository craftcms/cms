import type {FormNodePayload} from '../types';

export function rowFields(nodes: FormNodePayload[]): FormNodePayload[] {
  return nodes.flatMap((node) =>
    node.control ? [node] : rowFields(node.children ?? [])
  );
}
