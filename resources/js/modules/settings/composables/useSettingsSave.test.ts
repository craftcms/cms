import {describe, expect, it, vi} from 'vite-plus/test';
import type {InertiaForm} from '@inertiajs/vue3';

vi.mock('@vueuse/core', () => ({
  useEventListener: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({props: {}}),
}));

import {useSettingsSave} from './useSettingsSave';

describe('useSettingsSave', () => {
  it('does not submit while saving is disabled', () => {
    const form = {
      clearErrors: vi.fn(),
      transform: vi.fn(),
      submit: vi.fn(),
      data: vi.fn(),
      isDirty: false,
    };
    form.clearErrors.mockReturnValue(form);
    form.transform.mockReturnValue(form);

    const {save} = useSettingsSave(
      form as unknown as InertiaForm<Record<string, never>>,
      () => '/settings',
      {
        disabled: () => true,
      }
    );

    save();

    expect(form.clearErrors).not.toHaveBeenCalled();
    expect(form.submit).not.toHaveBeenCalled();
  });
});
