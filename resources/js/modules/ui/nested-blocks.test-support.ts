import type {UiPayload} from './types';

/**
 * A layout holding one Matrix field at `fields.blocks`, with a block for each
 * key of `headings` that has a Heading field holding its value. The blocks in
 * `reused` come as the placeholders a server sends for nested UIs a request
 * didn't change, with no nodes and no field values.
 */
export function nestedBlocksPayload(
  headings: Record<string, string>,
  reused: string[] = []
): UiPayload {
  const path = ['fields', 'blocks'];

  return {
    scope: [],
    refreshable: false,
    nodes: [
      {
        type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
        component: 'craft:field',
        props: {label: 'Blocks'},
        control: {
          type: 'CraftCms\\Cms\\Ui\\Controls\\NestedElementBlocks',
          component: 'craft:nested-element-blocks',
          props: {
            entryTypes: [{value: 'block', label: 'Block'}],
            addLabel: 'Add an entry',
            minEntries: null,
            maxEntries: null,
          },
          path,
          mode: 'editable',
          deltaGroup: path,
          uis: Object.keys(headings).map((uid) => {
            const scope = [...path, 'entries', uid];

            return reused.includes(uid)
              ? {scope, refreshable: true, nodes: [], reused: true}
              : {
                  scope,
                  refreshable: true,
                  nodes: [
                    {
                      type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
                      component: 'craft:field',
                      props: {label: 'Heading'},
                      control: {
                        type: 'CraftCms\\Cms\\Ui\\Controls\\Text',
                        component: 'craft:text',
                        props: {inputType: 'text'},
                        path: [...scope, 'fields', 'heading'],
                        mode: 'editable',
                        deltaGroup: path,
                      },
                    },
                  ],
                };
          }),
        },
      },
    ],
    values: {
      fields: {
        blocks: {
          entries: Object.fromEntries(
            Object.entries(headings).map(([uid, heading]) => [
              uid,
              reused.includes(uid)
                ? {type: 'block', enabled: true}
                : {type: 'block', enabled: true, fields: {heading}},
            ])
          ),
          sortOrder: Object.keys(headings),
        },
      },
    },
    errors: [],
    globalErrors: [],
  };
}

/** The Heading input of block `uid` in a {@link nestedBlocksPayload} layout. */
export function nestedBlockHeading(
  root: ParentNode,
  uid: string
): HTMLInputElement | null {
  return root.querySelector<HTMLInputElement>(
    `input[name="fields[blocks][entries][${uid}][fields][heading]"]`
  );
}
