import type {DefineComponent} from 'vue';

type MaybePromise<T> = T | Promise<T>;

export type InertiaPageComponent = DefineComponent;

export type InertiaPageModule = {
  default: InertiaPageComponent;
};

export type InertiaPageLoader = () => MaybePromise<
  InertiaPageComponent | InertiaPageModule
>;

export type InertiaPageRegistration = InertiaPageComponent | InertiaPageLoader;

export type InertiaPageGlob = Record<string, InertiaPageLoader>;

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

      return resolveRegisteredPage(page);
    },
  };
}

export async function resolveCoreInertiaPage(
  name: string,
  pages: InertiaPageGlob
): Promise<InertiaPageComponent | undefined> {
  const loader = pages[`../pages/${name}.vue`];

  if (loader === undefined) {
    return undefined;
  }

  return normalizePageModule(await loader());
}

export async function resolveInertiaPage(
  name: string,
  corePages: InertiaPageGlob,
  registry: InertiaPageRegistry
): Promise<InertiaPageComponent> {
  const corePage = await resolveCoreInertiaPage(name, corePages);

  if (corePage !== undefined) {
    return corePage;
  }

  const registeredPage = await registry.resolve(name);

  if (registeredPage !== undefined) {
    return registeredPage;
  }

  throw new Error(`Page not found: ${name}`);
}

async function resolveRegisteredPage(
  page: InertiaPageRegistration
): Promise<InertiaPageComponent> {
  if (!isInertiaPageLoader(page)) {
    return page;
  }

  return normalizePageModule(await page());
}

function isInertiaPageLoader(
  page: InertiaPageRegistration
): page is InertiaPageLoader {
  return typeof page === 'function';
}

function normalizePageModule(
  page: InertiaPageComponent | InertiaPageModule
): InertiaPageComponent {
  if (isInertiaPageModule(page)) {
    return page.default;
  }

  return page;
}

function isInertiaPageModule(
  page: InertiaPageComponent | InertiaPageModule
): page is InertiaPageModule {
  return typeof page === 'object' && page !== null && 'default' in page;
}
