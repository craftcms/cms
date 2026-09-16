import {createApp, h} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import FormActions from './FormActions.vue';

describe('FormActions', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  it('explains why an additional action is disabled', () => {
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
            },
          ],
        }),
    });
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);

    const applyButton = [...container.querySelectorAll('craft-button')].find(
      (button) => button.textContent?.trim() === 'Apply draft'
    ) as HTMLElementTagNameMap['craft-button'];
    const infoIcon =
      container.querySelector<HTMLElementTagNameMap['craft-info-icon']>(
        'craft-info-icon'
      )!;

    expect(applyButton.disabled).toBe(true);
    expect(infoIcon.textContent).toContain(
      'This draft must be approved before it can be applied.'
    );
    expect(infoIcon.label).toBe('Why “Apply draft” is unavailable');
  });
});
