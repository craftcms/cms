import type {DefineComponent} from 'vue';

type MaybePromise<T> = T | Promise<T>;

export type InertiaPageComponent = DefineComponent<any, any, any>;

export type InertiaPageModule = {
  default: InertiaPageComponent;
};

export type InertiaPageLoader = () => MaybePromise<
  InertiaPageComponent | InertiaPageModule
>;

export type InertiaPageRegistration = InertiaPageComponent | InertiaPageLoader;

export type InertiaPageGlob = Record<string, InertiaPageLoader>;

function isPageLoader(
  registration: InertiaPageRegistration
): registration is InertiaPageLoader {
  return (
    registration instanceof Function &&
    !('render' in registration) &&
    !('setup' in registration)
  );
}

const coreInertiaPages = import.meta.glob<InertiaPageModule>(
  '../pages/**/*.vue'
);

export interface InertiaPageRegistry {
  register(name: string, componentOrLoader: InertiaPageRegistration): void;
  resolve(name: string): Promise<InertiaPageComponent | undefined>;
}

export function createInertiaPageRegistry(): InertiaPageRegistry {
  const pages = new Map<string, InertiaPageRegistration>();

  return {
    register(name, componentOrLoader) {
      const existingPage = pages.get(name);

      if (existingPage !== undefined && existingPage !== componentOrLoader) {
        throw new Error(`Inertia page already registered: ${name}`);
      }

      pages.set(name, componentOrLoader);
    },

    async resolve(name) {
      const page = pages.get(name);

      if (page === undefined) {
        return undefined;
      }

      const resolvedPage = isPageLoader(page) ? await page() : page;

      if ('default' in resolvedPage) {
        return resolvedPage.default;
      }

      return resolvedPage;
    },
  };
}

export const inertiaPageRegistry = createInertiaPageRegistry();

export async function resolveCoreInertiaPage(
  name: string,
  pages: InertiaPageGlob = coreInertiaPages
): Promise<InertiaPageComponent | undefined> {
  const loader = pages[`../pages/${name}.vue`];

  if (loader === undefined) {
    return undefined;
  }

  const page = await loader();

  if ('default' in page) {
    return page.default;
  }

  return page;
}

/**
 * Where a resolved page came from. Plugin-facing layout components check it
 * to make sure they're only used on a page the plugin owns.
 *
 * Stored on the component rather than in a WeakMap: `createApp()` mounts a
 * shallow copy of its root component, and a symbol key survives the copy.
 */
const PageOrigin = Symbol('inertiaPageOrigin');

type InertiaPageOrigin = 'core' | 'plugin';

function tagPage(page: InertiaPageComponent, origin: InertiaPageOrigin) {
  (page as unknown as Record<symbol, InertiaPageOrigin>)[PageOrigin] = origin;
}

export function inertiaPageOrigin(
  component: unknown
): InertiaPageOrigin | undefined {
  return typeof component === 'object' && component !== null
    ? (component as Record<symbol, InertiaPageOrigin | undefined>)[PageOrigin]
    : undefined;
}

export async function resolveInertiaPage(
  name: string,
  registry: InertiaPageRegistry = inertiaPageRegistry,
  corePages: InertiaPageGlob = coreInertiaPages
): Promise<InertiaPageComponent> {
  const corePage = await resolveCoreInertiaPage(name, corePages);

  if (corePage !== undefined) {
    tagPage(corePage, 'core');

    return corePage;
  }

  const registeredPage = await registry.resolve(name);

  if (registeredPage !== undefined) {
    tagPage(registeredPage, 'plugin');

    return registeredPage;
  }

  throw new Error(`Page not found: ${name}`);
}
