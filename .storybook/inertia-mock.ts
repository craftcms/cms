import {type App, defineComponent, h, type PropType, reactive} from 'vue';

/**
 * Default page props for Storybook
 * These can be overridden per-story using parameters.inertia
 */
export const defaultPageProps = {
  craft: {
    system: {
      name: 'Craft CMS',
      icon: null,
    },
    app: {
      version: '6.0.0',
      edition: {
        name: 'Pro' as const,
        handle: 'pro' as const,
        value: 2 as const,
      },
    },
    site: {
      url: 'https://example.com',
    },
    currentUser: {
      id: 1,
      username: 'admin',
      email: 'admin@example.com',
      admin: true,
    },
    nav: [],
    actionUrl: '/actions/',
    cpUrl: '/admin/',
    baseApiUrl: '/api/',
  },
  flash: {
    success: null,
    error: null,
  },
  readOnly: false,
};

export type PageProps = typeof defaultPageProps & Record<string, unknown>;

/**
 * Reactive page state that can be modified by stories
 */
export const pageState = reactive({
  props: {...defaultPageProps} as PageProps,
  url: '/',
  component: 'Story',
  version: '1',
  scrollRegions: [],
  rememberedState: {},
  clearHistory: false,
  encryptHistory: false,
});

/**
 * Reset page props to defaults, optionally merging with overrides
 */
export function setPageProps(overrides: Partial<PageProps> = {}) {
  pageState.props = deepMerge({...defaultPageProps}, overrides) as PageProps;
}

/**
 * Deep merge utility
 */
function deepMerge<T extends Record<string, unknown>>(
  target: T,
  source: Partial<T>
): T {
  const result = {...target};

  for (const key in source) {
    if (Object.prototype.hasOwnProperty.call(source, key)) {
      const sourceValue = source[key];
      const targetValue = result[key];

      if (
        sourceValue &&
        typeof sourceValue === 'object' &&
        !Array.isArray(sourceValue) &&
        targetValue &&
        typeof targetValue === 'object' &&
        !Array.isArray(targetValue)
      ) {
        result[key] = deepMerge(
          targetValue as Record<string, unknown>,
          sourceValue as Record<string, unknown>
        ) as T[Extract<keyof T, string>];
      } else {
        result[key] = sourceValue as T[Extract<keyof T, string>];
      }
    }
  }

  return result;
}

/**
 * Mock usePage composable
 */
export function usePage<T = PageProps>() {
  return pageState as unknown as {
    props: T;
    url: string;
    component: string;
    version: string;
  };
}

/**
 * Mock router for Inertia
 */
export const router = {
  visit: (url: string, options?: Record<string, unknown>) => {
    console.log('[Storybook] router.visit:', url, options);
  },
  get: (
    url: string,
    data?: Record<string, unknown>,
    options?: Record<string, unknown>
  ) => {
    console.log('[Storybook] router.get:', url, data, options);
  },
  post: (
    url: string,
    data?: Record<string, unknown>,
    options?: Record<string, unknown>
  ) => {
    console.log('[Storybook] router.post:', url, data, options);
  },
  put: (
    url: string,
    data?: Record<string, unknown>,
    options?: Record<string, unknown>
  ) => {
    console.log('[Storybook] router.put:', url, data, options);
  },
  patch: (
    url: string,
    data?: Record<string, unknown>,
    options?: Record<string, unknown>
  ) => {
    console.log('[Storybook] router.patch:', url, data, options);
  },
  delete: (url: string, options?: Record<string, unknown>) => {
    console.log('[Storybook] router.delete:', url, options);
  },
  reload: (options?: Record<string, unknown>) => {
    console.log('[Storybook] router.reload:', options);
  },
  replace: (url: string, options?: Record<string, unknown>) => {
    console.log('[Storybook] router.replace:', url, options);
  },
  on: (event: string, callback: (...args: unknown[]) => void) => {
    console.log('[Storybook] router.on:', event);
    return () => {};
  },
};

/**
 * Mock useForm composable
 */
export interface InertiaForm<TForm extends Record<string, unknown>> {
  data(): TForm;
  transform(callback: (data: TForm) => Record<string, unknown>): this;
  defaults(): this;
  defaults(field: keyof TForm, value: unknown): this;
  defaults(fields: Partial<TForm>): this;
  reset(...fields: (keyof TForm)[]): this;
  clearErrors(...fields: (keyof TForm)[]): this;
  setError(field: keyof TForm, value: string): this;
  setError(errors: Record<keyof TForm, string>): this;
  submit(method: string, url: string, options?: Record<string, unknown>): void;
  get(url: string, options?: Record<string, unknown>): void;
  post(url: string, options?: Record<string, unknown>): void;
  put(url: string, options?: Record<string, unknown>): void;
  patch(url: string, options?: Record<string, unknown>): void;
  delete(url: string, options?: Record<string, unknown>): void;
  cancel(): void;
  errors: Partial<Record<keyof TForm, string>>;
  hasErrors: boolean;
  processing: boolean;
  progress: {percentage: number} | null;
  wasSuccessful: boolean;
  recentlySuccessful: boolean;
  isDirty: boolean;
}

export function useForm<TForm extends Record<string, unknown>>(
  initialData: TForm
): InertiaForm<TForm> & TForm;
export function useForm<TForm extends Record<string, unknown>>(
  rememberKey: string,
  initialData: TForm
): InertiaForm<TForm> & TForm;
export function useForm<TForm extends Record<string, unknown>>(
  rememberKeyOrData: string | TForm,
  maybeData?: TForm
): InertiaForm<TForm> & TForm {
  const data =
    typeof rememberKeyOrData === 'string' ? maybeData! : rememberKeyOrData;
  const formData = reactive({...data}) as TForm;

  const form = reactive({
    ...formData,
    errors: {} as Partial<Record<keyof TForm, string>>,
    hasErrors: false,
    processing: false,
    progress: null as {percentage: number} | null,
    wasSuccessful: false,
    recentlySuccessful: false,
    isDirty: false,
    data() {
      return formData;
    },
    transform(callback: (data: TForm) => Record<string, unknown>) {
      return this;
    },
    defaults(...args: unknown[]) {
      return this;
    },
    reset(...fields: (keyof TForm)[]) {
      if (fields.length === 0) {
        Object.assign(formData, data);
      } else {
        fields.forEach((field) => {
          (formData as Record<string, unknown>)[field as string] = data[field];
        });
      }
      return this;
    },
    clearErrors(...fields: (keyof TForm)[]) {
      if (fields.length === 0) {
        this.errors = {};
      } else {
        fields.forEach((field) => {
          delete this.errors[field];
        });
      }
      this.hasErrors = Object.keys(this.errors).length > 0;
      return this;
    },
    setError(
      fieldOrErrors: keyof TForm | Record<keyof TForm, string>,
      maybeValue?: string
    ) {
      if (typeof fieldOrErrors === 'string') {
        this.errors[fieldOrErrors] = maybeValue;
      } else {
        Object.assign(this.errors, fieldOrErrors);
      }
      this.hasErrors = true;
      return this;
    },
    submit(method: string, url: string, options?: Record<string, unknown>) {
      console.log('[Storybook] form.submit:', method, url, formData, options);
    },
    get(url: string, options?: Record<string, unknown>) {
      this.submit('get', url, options);
    },
    post(url: string, options?: Record<string, unknown>) {
      this.submit('post', url, options);
    },
    put(url: string, options?: Record<string, unknown>) {
      this.submit('put', url, options);
    },
    patch(url: string, options?: Record<string, unknown>) {
      this.submit('patch', url, options);
    },
    delete(url: string, options?: Record<string, unknown>) {
      this.submit('delete', url, options);
    },
    cancel() {
      console.log('[Storybook] form.cancel');
    },
  }) as InertiaForm<TForm> & TForm;

  return form;
}

/**
 * Mock Head component
 */
export const Head = defineComponent({
  name: 'Head',
  props: {
    title: String,
  },
  setup(props, {slots}) {
    // In Storybook, we just render nothing for Head
    return () => null;
  },
});

/**
 * Mock Link component
 */
export interface InertiaLinkProps {
  href: string;
  method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
  data?: Record<string, unknown>;
  replace?: boolean;
  preserveScroll?: boolean;
  preserveState?: boolean;
  only?: string[];
  headers?: Record<string, string>;
  as?: string;
}

export const Link = defineComponent({
  name: 'Link',
  props: {
    href: {
      type: String,
      required: true,
    },
    method: {
      type: String as PropType<'get' | 'post' | 'put' | 'patch' | 'delete'>,
      default: 'get',
    },
    data: Object as PropType<Record<string, unknown>>,
    replace: Boolean,
    preserveScroll: Boolean,
    preserveState: Boolean,
    only: Array as PropType<string[]>,
    headers: Object as PropType<Record<string, string>>,
    as: {
      type: String,
      default: 'a',
    },
  },
  setup(props, {slots, attrs}) {
    return () =>
      h(
        props.as,
        {
          ...attrs,
          href: props.href,
          onClick: (e: Event) => {
            e.preventDefault();
            console.log(
              '[Storybook] Link clicked:',
              props.href,
              props.method,
              props.data
            );
          },
        },
        slots.default?.()
      );
  },
});

/**
 * Mock Form component
 */
export const Form = defineComponent({
  name: 'Form',
  props: {
    method: {
      type: String as PropType<'get' | 'post' | 'put' | 'patch' | 'delete'>,
      default: 'post',
    },
    action: String,
    data: Object as PropType<Record<string, unknown>>,
    preserveScroll: Boolean,
    preserveState: Boolean,
    only: Array as PropType<string[]>,
    headers: Object as PropType<Record<string, string>>,
  },
  setup(props, {slots, attrs}) {
    return () =>
      h(
        'form',
        {
          ...attrs,
          action: props.action,
          method: props.method === 'get' ? 'get' : 'post',
          onSubmit: (e: Event) => {
            e.preventDefault();
            console.log(
              '[Storybook] Form submitted:',
              props.action,
              props.method,
              props.data
            );
          },
        },
        slots.default?.()
      );
  },
});

/**
 * Mock Deferred component - renders children immediately in Storybook
 */
export const Deferred = defineComponent({
  name: 'Deferred',
  props: {
    data: {
      type: [String, Array] as PropType<string | string[]>,
      required: true,
    },
  },
  setup(props, {slots}) {
    // In Storybook, render children immediately (no deferred loading)
    return () => slots.default?.();
  },
});

/**
 * Mock createInertiaApp - not typically used in Storybook stories
 */
export function createInertiaApp(options: Record<string, unknown>) {
  console.log('[Storybook] createInertiaApp called - this is a mock');
  return Promise.resolve();
}

/**
 * Install the Inertia mock as a Vue plugin
 */
export function installInertiaMock(app: App) {
  // Provide the page object for usePage
  app.config.globalProperties.$page = pageState;
  app.config.globalProperties.$inertia = router;

  // Register global components
  app.component('Head', Head);
  app.component('Link', Link);
  app.component('InertiaLink', Link);

  // Also provide via provide/inject for composition API
  app.provide('$inertia', router);
  app.provide('$page', pageState);
}
