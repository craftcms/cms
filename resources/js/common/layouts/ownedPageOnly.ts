import {
  defineComponent,
  getCurrentInstance,
  h,
  type Component,
  type ComponentInternalInstance,
} from 'vue';
import {inertiaPageOrigin} from '@/bootstrap/inertia-pages';

/**
 * Whether the nearest Inertia page above this component came from a plugin.
 *
 * A plugin's own page is the only place it may take over the screen's chrome.
 * Anything it renders on someone else's page — a dashboard widget, a UI node,
 * a details tab — sits under a core page here and is refused.
 */
function onPluginPage(instance: ComponentInternalInstance | null): boolean {
  for (let parent = instance?.parent; parent; parent = parent.parent) {
    const origin = inertiaPageOrigin(parent.type);

    if (origin) {
      return origin === 'plugin';
    }
  }

  return false;
}

/**
 * Wraps a layout component for plugins, rendering it only on a page the
 * plugin owns. Core imports the component directly and isn't restricted.
 */
export function ownedPageOnly(name: string, component: Component): Component {
  return defineComponent({
    name: `OwnedPageOnly(${name})`,
    inheritAttrs: false,
    setup(_props, {attrs, slots}) {
      const allowed = onPluginPage(getCurrentInstance());

      if (import.meta.env.DEV && !allowed) {
        console.warn(
          `[${name}] Only renders on a page your plugin registered with \`Cp.$inertia\`; ` +
            "it can't change the layout of a page it doesn't own."
        );
      }

      return () => (allowed ? h(component, attrs, slots) : null);
    },
  });
}
