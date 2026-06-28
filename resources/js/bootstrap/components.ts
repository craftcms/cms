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
  let app: App | null = null;

  function registerWithApp(
    app: App,
    name: string,
    componentOrLoader: CpComponentRegistration
  ) {
    app.component(
      name,
      isLoader(componentOrLoader)
        ? asyncComponent(componentOrLoader)
        : componentOrLoader
    );
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
        registerWithApp(app, name, componentOrLoader);
      }
    },

    install(installedApp) {
      if (app === installedApp) {
        return;
      }

      app = installedApp;
      components.forEach((componentOrLoader, name) => {
        registerWithApp(installedApp, name, componentOrLoader);
      });
    },
  };
}
