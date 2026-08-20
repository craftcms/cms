declare module '*.vue' {
  import type {DefineComponent} from 'vue';
  type ComponentProp =
    | string
    | number
    | boolean
    | bigint
    | symbol
    | object
    | null
    | undefined;
  const component: DefineComponent<Record<string, ComponentProp>>;
  export default component;
}
