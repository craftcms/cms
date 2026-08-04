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
 * Mock useForm composable
 */
export function useForm(rememberKeyOrData, maybeData) {
  const initialValues =
    typeof rememberKeyOrData === 'string' ? maybeData : rememberKeyOrData;
  const formData = reactive({...initialValues});
  const errors = {};

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
      console.log('[Storybook] form.submit:', ...args);
    },
    get(url, options) {
      console.log('[Storybook] form.get:', url, options);
    },
    post(url, options) {
      console.log('[Storybook] form.post:', url, options);
    },
    put(url, options) {
      console.log('[Storybook] form.put:', url, options);
    },
    patch(url, options) {
      console.log('[Storybook] form.patch:', url, options);
    },
    delete(url, options) {
      console.log('[Storybook] form.delete:', url, options);
    },
    cancel() {
      console.log('[Storybook] form.cancel');
    },
    dontRemember: () => formProps,
    optimistic: () => formProps,
    withPrecognition: () => formProps,
  };

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
