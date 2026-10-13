import {shallowReactive} from 'vue';
import type {Component} from 'vue';
import type {
  ElementEditPayload,
  ElementEditPayloadUpdater,
  ElementFormActionSubmitter,
} from '@/modules/elements/composables/useElementEditor';

export interface ElementDetailsPanelContext {
  payload: ElementEditPayload;
  /** Whether this panel is the open one. */
  active: boolean;
  refreshToken: number;
  updatePayload: ElementEditPayloadUpdater;
  submitAction: ElementFormActionSubmitter;
}

export interface ElementDetailsPanelStatus {
  label: string;
  indicator: string;
}

export interface ElementDetailsPanelDescriptor {
  /** A plugin-scoped identifier that remains stable across registrations. */
  id: string;
  label: string;
  icon: string;
  component: Component;
  /** Optional controls rendered at the end of the panel header. */
  headerActionsComponent?: Component;
  order?: number;
  visible?: (payload: ElementEditPayload) => boolean;
  status?: (payload: ElementEditPayload) => ElementDetailsPanelStatus | null;
  props?: (context: ElementDetailsPanelContext) => Record<string, unknown>;
}

export interface ElementDetailsPanelRegistry {
  readonly panels: readonly ElementDetailsPanelDescriptor[];
  /** @deprecated Use {@link ElementDetailsPanelRegistry.panels}. */
  readonly tabs: readonly ElementDetailsPanelDescriptor[];
  register(descriptor: ElementDetailsPanelDescriptor): void;
  hasVisible(payload: ElementEditPayload): boolean;
}

/** @deprecated Use {@link ElementDetailsPanelContext}. */
export type ElementDetailsTabContext = ElementDetailsPanelContext;

/** @deprecated Use {@link ElementDetailsPanelStatus}. */
export type ElementDetailsTabStatus = ElementDetailsPanelStatus;

/** @deprecated Use {@link ElementDetailsPanelDescriptor}. */
export type ElementDetailsTabDescriptor = ElementDetailsPanelDescriptor;

/** @deprecated Use {@link ElementDetailsPanelRegistry}. */
export type ElementDetailsTabRegistry = ElementDetailsPanelRegistry;

const warned = new Set<string>();

/** Logs a deprecation once per name, so a plugin reading it in a loop isn't noisy. */
export function warnDeprecated(name: string, replacement: string): void {
  if (warned.has(name)) {
    return;
  }

  warned.add(name);
  console.warn(`${name} is deprecated. Use ${replacement} instead.`);
}

export function createElementDetailsPanelRegistry(): ElementDetailsPanelRegistry {
  const panels = shallowReactive<ElementDetailsPanelDescriptor[]>([]);

  return {
    get panels() {
      return panels;
    },

    get tabs() {
      warnDeprecated(
        'Cp.$elementDetailsPanels.tabs',
        'Cp.$elementDetailsPanels.panels'
      );

      return panels;
    },

    register(descriptor) {
      const existingDescriptor = panels.find(
        (panel) => panel.id === descriptor.id
      );

      if (
        existingDescriptor !== undefined &&
        existingDescriptor !== descriptor
      ) {
        throw new Error(
          `Element details panel already registered: ${descriptor.id}`
        );
      }

      if (existingDescriptor !== undefined) {
        return;
      }

      panels.push(descriptor);
    },

    hasVisible(payload) {
      return panels.some((panel) => panel.visible?.(payload) ?? true);
    },
  };
}

export const elementDetailsPanelRegistry = createElementDetailsPanelRegistry();
