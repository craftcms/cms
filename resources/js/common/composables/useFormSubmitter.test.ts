import {effectScope, nextTick, reactive} from 'vue';
import type {InertiaForm} from '@inertiajs/vue3';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {PRIMARY_SUBMITTER, useFormSubmitter} from './useFormSubmitter';

function createForm() {
  return reactive({processing: false}) as unknown as InertiaForm<any>;
}

describe('useFormSubmitter', () => {
  const scopes: ReturnType<typeof effectScope>[] = [];

  function submitterIn(form: InertiaForm<any>) {
    const scope = effectScope();
    scopes.push(scope);
    return scope.run(() => useFormSubmitter(form))!;
  }

  afterEach(() => {
    scopes.splice(0).forEach((scope) => scope.stop());
    vi.useRealTimers();
  });

  it('gives an unclaimed submission to the submit button', async () => {
    const form = createForm();
    const submitter = submitterIn(form);

    form.processing = true;
    await nextTick();

    expect(submitter.isSubmitting(PRIMARY_SUBMITTER)).toBe(true);

    form.processing = false;
    await nextTick();

    expect(submitter.isSubmitting(PRIMARY_SUBMITTER)).toBe(false);
  });

  it('agrees on the submitter across components sharing a form', async () => {
    const form = createForm();
    const shell = submitterIn(form);
    const page = submitterIn(form);

    page.claim('Create a draft');
    form.processing = true;
    await nextTick();

    expect(page.isSubmitting('Create a draft')).toBe(true);
    expect(shell.isSubmitting('Create a draft')).toBe(true);
    expect(shell.isSubmitting(PRIMARY_SUBMITTER)).toBe(false);
  });

  it('keeps forms apart', async () => {
    const one = createForm();
    const two = createForm();
    submitterIn(one).claim('View');
    const other = submitterIn(two);

    two.processing = true;
    await nextTick();

    expect(other.isSubmitting('View')).toBe(false);
    expect(other.isSubmitting(PRIMARY_SUBMITTER)).toBe(true);
  });

  it('lets go of a claim whose click started nothing', async () => {
    vi.useFakeTimers();
    const form = createForm();
    const submitter = submitterIn(form);

    submitter.claim('View');
    vi.runAllTimers();

    form.processing = true;
    await nextTick();

    expect(submitter.isSubmitting('View')).toBe(false);
    expect(submitter.isSubmitting(PRIMARY_SUBMITTER)).toBe(true);
  });
});
