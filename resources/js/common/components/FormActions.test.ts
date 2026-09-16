import {createApp, h} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import FormActions from './FormActions.vue';

describe('FormActions', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  it('keeps an unavailable additional action focusable with an explanation', () => {
    const onClick = vi.fn();
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(FormActions, {
          form: {
            processing: false,
            recentlySuccessful: false,
            hasErrors: false,
          },
          additionalButtons: [
            {
              label: 'Apply draft',
              disabled: true,
              disabledReason:
                'This draft must be approved before it can be applied.',
              onClick,
            },
          ],
        }),
    });
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);

    const applyButton = [...container.querySelectorAll('craft-button')].find(
      (button) => button.textContent?.trim() === 'Apply draft'
    ) as HTMLElementTagNameMap['craft-button'];
    const tooltip =
      container.querySelector<HTMLElementTagNameMap['craft-tooltip']>(
        'craft-tooltip'
      )!;

    expect(applyButton.disabled).toBe(false);
    expect(applyButton.getAttribute('aria-disabled')).toBe('true');
    expect(applyButton.id).toBe('disabled-form-action-0');
    expect(tooltip.for).toBe(applyButton.id);
    expect(tooltip.textContent).toContain(
      'This draft must be approved before it can be applied.'
    );

    applyButton.click();

    expect(onClick).not.toHaveBeenCalled();
  });
});
