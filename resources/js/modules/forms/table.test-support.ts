import type {FormNodePayload} from './types';

export function columnMetadata(
  scope: string[],
  cellTypes: Array<{label: string; value: string}>,
  deltaGroup: string[]
): FormNodePayload[] {
  const fields = [
    ['heading', 'Text', 'craft:text'],
    ['handle', 'Handle', 'craft:handle'],
    ['width', 'Text', 'craft:text'],
    ['type', 'Choice', 'craft:choice'],
  ] as const;

  return fields.map(
    ([property, type, component]): FormNodePayload => ({
      type: 'Field',
      component: 'craft:field',
      props: {label: property},
      control: {
        type: type,
        component: component,
        mode: 'editable',
        path: [...scope, property],
        deltaGroup,
        props:
          property === 'type'
            ? {options: cellTypes, presentation: 'select', placeholder: false}
            : {},
      },
    })
  );
}
