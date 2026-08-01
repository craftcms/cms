import type {InjectionKey} from 'vue';

export const editableTableColumnsChangedEvent =
  'craft:editable-table-columns-changed';

export const editableTableColumnsEventTarget: InjectionKey<EventTarget> =
  Symbol('editable-table-columns-event-target');
