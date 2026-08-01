import {defineComponent, h, nextTick, ref} from 'vue';
import {expect, it, vi} from 'vite-plus/test';
import {
  mountEmbeddedInertiaPage,
  slideoutRequestHeaders,
} from './cp-screen-slideout';

it('mounts and unmounts an embedded Inertia page with exposed errors', async () => {
  const errors = ref<Record<string, unknown>>({});
  const component = defineComponent({
    props: {label: {type: String, required: true}},
    setup(props, {expose}) {
      expose({
        setErrors(value: Record<string, unknown>) {
          errors.value = value;
        },
      });

      return () => h('div', {'data-embedded-page': ''}, props.label);
    },
  });
  const resolve = vi.fn(async () => component);
  const container = document.createElement('div');

  const mounted = await mountEmbeddedInertiaPage(
    container,
    'settings/fields/Edit',
    {label: 'Field editor'},
    resolve
  );

  expect(resolve).toHaveBeenCalledWith('settings/fields/Edit');
  expect(container.querySelector('[data-embedded-page]')?.textContent).toBe(
    'Field editor'
  );

  mounted.page.setErrors?.({name: ['Name is required.']});
  await nextTick();
  expect(errors.value).toEqual({name: ['Name is required.']});

  mounted.app.unmount();
  expect(container.textContent).toBe('');
});

it('omits legacy namespace extraction for embedded Inertia submissions', () => {
  expect(slideoutRequestHeaders(true, 'abc123')).toEqual({});
  expect(slideoutRequestHeaders(false, 'abc123')).toEqual({
    'X-Craft-Namespace': 'abc123',
  });
});
