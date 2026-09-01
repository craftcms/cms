import {defineComponent, h, reactive} from 'vue';

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
        name: 'Pro',
        handle: 'pro',
        value: 2,
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

/**
 * Reactive page state that can be modified by stories
 */
export const pageState = reactive({
  props: {...defaultPageProps, errors: {}, deferred: {}},
  url: '/',
  component: 'Story',
  version: '1',
  clearHistory: false,
  encryptHistory: false,
  flash: {},
  rememberedState: {},
});

/**
 * Reset page props to defaults, optionally merging with overrides
 */
export function setPageProps(overrides = {}) {
  pageState.props = deepMerge({...defaultPageProps}, overrides);
}

/**
 * Deep merge utility
 */
function deepMerge(target, source) {
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
        result[key] = deepMerge(targetValue, sourceValue);
      } else {
        result[key] = sourceValue;
      }
    }
  }

  return result;
}

/**
 * Mock usePage composable
 */
export function usePage() {
  return pageState;
}

/**
 * Mock setLayoutProps.
 *
 * Upstream this pushes props at whichever layout Inertia rendered. Storybook
 * renders a story, not a page, so there's no layout to reach — but
 * `useAppLayout()` falls back to this whenever no shell has provided a sink,
 * which is every story that isn't wrapped in one.
 */
export function setLayoutProps(props) {
  console.log('[Storybook] setLayoutProps:', props);
}

/**
 * Mock router for Inertia
 */
export const router = {
  visit(url, options) {
    console.log('[Storybook] router.visit:', url, options);
  },
  get(url, data, options) {
    console.log('[Storybook] router.get:', url, data, options);
  },
  post(url, data, options) {
    console.log('[Storybook] router.post:', url, data, options);
  },
  put(url, data, options) {
    console.log('[Storybook] router.put:', url, data, options);
  },
  patch(url, data, options) {
    console.log('[Storybook] router.patch:', url, data, options);
  },
  delete(url, options) {
    console.log('[Storybook] router.delete:', url, options);
  },
  reload(options) {
    console.log('[Storybook] router.reload:', options);
  },
  replace(url, options) {
    console.log('[Storybook] router.replace:', url, options);
  },
  on(event, callback) {
    console.log('[Storybook] router.on:', event);
    return () => {};
  },
};

/**
 * The shape `useForm` and `useHttp` share: the form's own fields spread at the
 * top level, plus the request state and the chainable helpers.
 *
 * The two differ in exactly two ways, which is why this is one builder:
 * `useHttp`'s verbs resolve a promise (callers `await` them) and it carries a
 * `response`. Neither mock performs a request — they log, so a story shows what
 * *would* have been sent.
 *
 * @param initialValues - The form's starting data.
 * @param label - What to call this in the console.
 * @param async - Whether the verbs return a promise, as `useHttp`'s do.
 */
function createFormLike(initialValues, {label, async: isAsync}) {
  const formData = reactive({...initialValues});
  const errors = {};

  const send = (verb) => (url, options) => {
    console.log(`[Storybook] ${label}.${verb}:`, url, options);

    // `useHttp` callers `await` this, or chain off it. Resolving `null` keeps
    // them moving without inventing a response body they'd then render.
    return isAsync ? Promise.resolve(null) : undefined;
  };

  const formProps = {
    errors,
    hasErrors: false,
    processing: false,
    progress: null,
    wasSuccessful: false,
    recentlySuccessful: false,
    isDirty: false,
    data: () => formData,
    transform: () => formProps,
    defaults: () => formProps,
    reset(...fields) {
      if (fields.length === 0) {
        Object.assign(formData, initialValues);
      } else {
        fields.forEach((field) => {
          formData[field] = initialValues[field];
        });
      }
      return formProps;
    },
    clearErrors(...fields) {
      if (fields.length === 0) {
        Object.keys(errors).forEach((key) => delete errors[key]);
      } else {
        fields.forEach((field) => delete errors[field]);
      }
      formProps.hasErrors = Object.keys(errors).length > 0;
      return formProps;
    },
    resetAndClearErrors(...fields) {
      formProps.reset(...fields);
      formProps.clearErrors(...fields);
      return formProps;
    },
    setError(fieldOrErrors, maybeValue) {
      if (typeof fieldOrErrors === 'string') {
        errors[fieldOrErrors] = maybeValue;
      } else {
        Object.assign(errors, fieldOrErrors);
      }
      formProps.hasErrors = true;
      return formProps;
    },
    submit(...args) {
      console.log(`[Storybook] ${label}.submit:`, ...args);
      return isAsync ? Promise.resolve(null) : undefined;
    },
    get: send('get'),
    post: send('post'),
    put: send('put'),
    patch: send('patch'),
    delete: send('delete'),
    cancel() {
      console.log(`[Storybook] ${label}.cancel`);
    },
    dontRemember: () => formProps,
    optimistic: () => formProps,
    withPrecognition: () => formProps,
  };

  if (isAsync) {
    formProps.response = null;
    formProps.withAllErrors = () => formProps;
  }

  return {formData, formProps};
}

/**
 * Mock useForm composable
 */
export function useForm(rememberKeyOrData, maybeData) {
  const initialValues =
    typeof rememberKeyOrData === 'string' ? maybeData : rememberKeyOrData;

  const {formData, formProps} = createFormLike(initialValues, {
    label: 'form',
    async: false,
  });

  return reactive({...formData, ...formProps});
}

/**
 * Mock useHttp composable — the non-navigating request form.
 *
 * Overloaded upstream as `()`, `(data)`, `(rememberKey, data)`,
 * `(urlMethodPair, data)` and `(method, url, data)`. In every one of those the
 * data is the last argument, so that's all this needs to find; the method and
 * URL only matter to a request the mock never makes.
 */
export function useHttp(...args) {
  const last = args[args.length - 1];
  const resolved = typeof last === 'function' ? last() : last;

  // A lone string is a remember key, not data; `()` has nothing at all.
  const initialValues =
    resolved && typeof resolved === 'object' && !Array.isArray(resolved)
      ? resolved
      : {};

  const {formData, formProps} = createFormLike(initialValues, {
    label: 'http',
    async: true,
  });

  return reactive({...formData, ...formProps});
}

/**
 * Mock Head component
 */
export const Head = defineComponent({
  name: 'Head',
  props: {
    title: String,
  },
  setup() {
    return () => null;
  },
});

/**
 * Mock Link component
 */
export const Link = defineComponent({
  name: 'Link',
  props: {
    href: {
      type: String,
      required: true,
    },
    method: {
      type: String,
      default: 'get',
    },
    data: Object,
    replace: Boolean,
    preserveScroll: Boolean,
    preserveState: Boolean,
    only: Array,
    headers: Object,
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
          onClick: (e) => {
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
      type: String,
      default: 'post',
    },
    action: String,
    data: Object,
    preserveScroll: Boolean,
    preserveState: Boolean,
    only: Array,
    headers: Object,
  },
  setup(props, {slots, attrs}) {
    const formState = reactive({
      processing: false,
      errors: {},
      hasErrors: false,
      wasSuccessful: false,
      recentlySuccessful: false,
    });

    return () =>
      h(
        'form',
        {
          ...attrs,
          action: props.action,
          method: props.method === 'get' ? 'get' : 'post',
          onSubmit: (e) => {
            e.preventDefault();
            console.log(
              '[Storybook] Form submitted:',
              props.action,
              props.method,
              props.data
            );
          },
        },
        slots.default?.(formState)
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
      type: [String, Array],
      required: true,
    },
  },
  setup(props, {slots}) {
    return () => slots.default?.();
  },
});

/**
 * Mock createInertiaApp - not typically used in Storybook stories
 */
export function createInertiaApp(options) {
  console.log('[Storybook] createInertiaApp called - this is a mock');
  return Promise.resolve();
}

/**
 * Install the Inertia mock as a Vue plugin
 */
export function installInertiaMock(app) {
  app.config.globalProperties.$page = pageState;
  app.config.globalProperties.$inertia = router;

  app.component('Head', Head);
  app.component('Link', Link);
  app.component('InertiaLink', Link);

  app.provide('$inertia', router);
  app.provide('$page', pageState);
}
