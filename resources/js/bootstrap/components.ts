import type {App, Component} from 'vue';
import {defineAsyncComponent} from 'vue';

type MaybePromise<T> = T | Promise<T>;
type ComponentApp = Pick<App, 'component' | 'config'>;

export type CpComponentModule = {
  default: Component;
};

export type CpComponentLoader = () => MaybePromise<
  Component | CpComponentModule
>;

export type CpComponentRegistration = Component | CpComponentLoader;

export interface CpComponentRegistry {
  register(name: string, componentOrLoader: CpComponentRegistration): void;
  install(app: ComponentApp): void;
  uninstall(app: ComponentApp): void;
}

let nextAppId = 0;

function isLoader(
  componentOrLoader: CpComponentRegistration
): componentOrLoader is CpComponentLoader {
  if (!(componentOrLoader instanceof Function)) {
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
  const apps = new Set<ComponentApp>();

  function registerWithApp(
    app: ComponentApp,
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
      for (const app of apps) {
        registerWithApp(app, name, componentOrLoader);
      }
    },

    install(installedApp) {
      if (apps.has(installedApp)) {
        return;
      }

      installedApp.config.idPrefix = `craft-${++nextAppId}`;
      apps.add(installedApp);
      components.forEach((componentOrLoader, name) => {
        registerWithApp(installedApp, name, componentOrLoader);
      });
    },

    uninstall(app) {
      apps.delete(app);
    },
  };
}

export const cpComponentRegistry = createCpComponentRegistry();
