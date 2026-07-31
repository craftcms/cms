import type {App, Component} from 'vue';
import {defineAsyncComponent} from 'vue';

type MaybePromise<T> = T | Promise<T>;

export type CpComponentModule = {
  default: Component;
};

export type CpComponentLoader = () => MaybePromise<
  Component | CpComponentModule
>;

export type CpComponentRegistration = Component | CpComponentLoader;

export interface CpComponentRegistry {
  register(name: string, componentOrLoader: CpComponentRegistration): void;
  resolve(name: string): Component | undefined;
  install(app: App): void;
}

function isLoader(
  componentOrLoader: CpComponentRegistration
): componentOrLoader is CpComponentLoader {
  if (typeof componentOrLoader !== 'function') {
    return false;
  }

  return !('render' in componentOrLoader) && !('setup' in componentOrLoader);
}

function asyncComponent(loader: CpComponentLoader): Component {
  return defineAsyncComponent(async () => {
    const component = await loader();

    if ('default' in component) {
      return component.default;
    }

    return component;
  });
}

export function createCpComponentRegistry(): CpComponentRegistry {
  const components = new Map<string, CpComponentRegistration>();
  const resolvedComponents = new Map<string, Component>();
  let app: App | null = null;

  function resolve(name: string): Component | undefined {
    const existingComponent = resolvedComponents.get(name);

    if (existingComponent) {
      return existingComponent;
    }

    const registration = components.get(name);

    if (!registration) {
      return undefined;
    }

    const component = isLoader(registration)
      ? asyncComponent(registration)
      : registration;

    resolvedComponents.set(name, component);

    return component;
  }

  function registerWithApp(app: App, name: string) {
    app.component(name, resolve(name)!);
  }

  return {
    register(name, componentOrLoader) {
      const existingComponent = components.get(name);

      if (
        existingComponent !== undefined &&
        existingComponent !== componentOrLoader
      ) {
        throw new Error(`CP component already registered: ${name}`);
      }

      if (existingComponent !== undefined) {
        return;
      }

      components.set(name, componentOrLoader);
      if (app) {
        registerWithApp(app, name);
      }
    },

    resolve,

    install(installedApp) {
      if (app === installedApp) {
        return;
      }

      app = installedApp;
      components.forEach((_, name) => {
        registerWithApp(installedApp, name);
      });
    },
  };
}
