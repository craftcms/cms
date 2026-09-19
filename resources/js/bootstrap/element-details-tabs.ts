import {shallowReactive} from 'vue';
import type {Component} from 'vue';
import type {
  ElementEditPayload,
  ElementEditPayloadUpdater,
} from '@/modules/elements/composables/useElementEditor';

export interface ElementDetailsTabContext {
  payload: ElementEditPayload;
  active: boolean;
  refreshToken: number;
  updatePayload: ElementEditPayloadUpdater;
}

export interface ElementDetailsTabStatus {
  label: string;
  indicator: string;
}

export interface ElementDetailsTabDescriptor {
  /** A plugin-scoped identifier that remains stable across registrations. */
  id: string;
  label: string;
  icon: string;
  component: Component;
  order?: number;
  visible?: (payload: ElementEditPayload) => boolean;
  status?: (payload: ElementEditPayload) => ElementDetailsTabStatus | null;
  props?: (context: ElementDetailsTabContext) => Record<string, unknown>;
}

export interface ElementDetailsTabRegistry {
  readonly tabs: readonly ElementDetailsTabDescriptor[];
  register(descriptor: ElementDetailsTabDescriptor): void;
  hasVisible(payload: ElementEditPayload): boolean;
}

export function createElementDetailsTabRegistry(): ElementDetailsTabRegistry {
  const tabs = shallowReactive<ElementDetailsTabDescriptor[]>([]);

  return {
    get tabs() {
      return tabs;
    },

    register(descriptor) {
      const existingDescriptor = tabs.find((tab) => tab.id === descriptor.id);

      if (
        existingDescriptor !== undefined &&
        existingDescriptor !== descriptor
      ) {
        throw new Error(
          `Element details tab already registered: ${descriptor.id}`
        );
      }

      if (existingDescriptor !== undefined) {
        return;
      }

      tabs.push(descriptor);
    },

    hasVisible(payload) {
      return tabs.some((tab) => tab.visible?.(payload) ?? true);
    },
  };
}

export const elementDetailsTabRegistry = createElementDetailsTabRegistry();
