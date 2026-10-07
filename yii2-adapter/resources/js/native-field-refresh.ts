import {actionClient} from '@craftcms/ui';
import {rebaseFieldUi, isolateFieldUi} from '@/modules/ui/html-field-ui';
import {
  isRecord,
  pathsMatch,
  setValue,
  valueAt,
  visitControls,
} from '@/modules/ui/runtime';
import type {UiNodePayload, UiPayload} from '@/modules/ui/types';

/** Native fields captured by a plugin's HTML override stay native on refresh. */
actionClient.interceptors.response.use((response) => {
  if (
    !response.config.headers['X-Craft-Ui-Root-Scope'] ||
    typeof response.config.data !== 'string' ||
    !response.config.headers['X-Craft-Native-Field-Path'] ||
    !isRecord(response.data) ||
    !isRecord(response.data.ui) ||
    !Array.isArray(response.data.ui.nodes)
  ) {
    return response;
  }

  const fieldPath: string[] = JSON.parse(
    String(response.config.headers['X-Craft-Native-Field-Path'])
  );
  const form = response.data.ui as unknown as UiPayload;
  const unwrap = (nodes: UiNodePayload[]): void => {
    for (const node of nodes) {
      const control = node.control;
      const fragment = control?.props.fragment;
      if (
        control?.component === 'craft-legacy:html' &&
        pathsMatch(control.path, fieldPath) &&
        isRecord(fragment) &&
        typeof fragment.html === 'string'
      ) {
        const template = document.createElement('template');
        template.innerHTML = fragment.html;
        const blocks = template.content.querySelector<HTMLElement>(
          'craft-entry-field-layout-ui[data-field-path]'
        );
        const nested = template.content.querySelector<HTMLElement>(
          'craft-nested-elements-control[data-control]'
        );
        if (blocks?.dataset.payload && blocks.dataset.fieldPath) {
          const native = isolateFieldUi(
            rebaseFieldUi(
              JSON.parse(blocks.dataset.payload),
              JSON.parse(blocks.dataset.fieldPath),
              control.path
            ),
            control.path
          );
          node.control = {
            ...native.nodes[0]!.control!,
            deltaGroup: control.deltaGroup,
            mode: control.mode,
          };
          for (const nested of node.control.uis ?? []) {
            visitControls(nested.nodes, (child) =>
              Object.assign(child, {
                deltaGroup: control.deltaGroup,
                ...(control.mode !== 'editable' ? {mode: control.mode} : {}),
              })
            );
          }
          setValue(
            form.values,
            control.path,
            valueAt(native.values, control.path)
          );
          form.errors.splice(
            0,
            form.errors.length,
            ...form.errors.filter(
              (error) =>
                !control.path.every(
                  (segment, index) => error.path[index] === segment
                )
            ),
            ...native.errors
          );
        } else if (nested?.dataset.control) {
          node.control = {
            ...JSON.parse(nested.dataset.control),
            path: control.path,
            deltaGroup: control.deltaGroup,
            mode: control.mode,
          };
        }
      }

      unwrap(node.children ?? []);
      for (const nested of node.control?.uis ?? []) {
        unwrap(nested.nodes);
      }
    }
  };
  unwrap(form.nodes);

  return response;
});
