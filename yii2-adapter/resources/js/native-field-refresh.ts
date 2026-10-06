import {actionClient} from '@craftcms/ui';
import {
  rebaseFieldForm,
  isolateFieldForm,
} from '@/modules/forms/html-field-form';
import {
  isRecord,
  pathsMatch,
  setValue,
  valueAt,
  visitControls,
} from '@/modules/forms/runtime';
import type {FormNodePayload, FormPayload} from '@/modules/forms/types';

/** Native fields captured by a plugin's HTML override stay native on refresh. */
actionClient.interceptors.response.use((response) => {
  if (
    !response.config.headers['X-Craft-Form-Root-Scope'] ||
    typeof response.config.data !== 'string' ||
    !response.config.headers['X-Craft-Native-Field-Path'] ||
    !isRecord(response.data) ||
    !isRecord(response.data.form) ||
    !Array.isArray(response.data.form.nodes)
  ) {
    return response;
  }

  const fieldPath: string[] = JSON.parse(
    String(response.config.headers['X-Craft-Native-Field-Path'])
  );
  const form = response.data.form as unknown as FormPayload;
  const unwrap = (nodes: FormNodePayload[]): void => {
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
          'craft-entry-field-layout-form[data-field-path]'
        );
        const nested = template.content.querySelector<HTMLElement>(
          'craft-nested-elements-control[data-control]'
        );
        if (blocks?.dataset.payload && blocks.dataset.fieldPath) {
          const native = isolateFieldForm(
            rebaseFieldForm(
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
          for (const nested of node.control.forms ?? []) {
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
      for (const nested of node.control?.forms ?? []) {
        unwrap(nested.nodes);
      }
    }
  };
  unwrap(form.nodes);

  return response;
});
