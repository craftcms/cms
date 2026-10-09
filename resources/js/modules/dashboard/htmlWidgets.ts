import {
  appendBodyHtml,
  appendHeadHtml,
  appendElementHtml,
  type AppendHtmlDisposer,
} from '@craftcms/ui';
import {onUnmounted, provide, type InjectionKey} from 'vue';

export type RenderHtmlWidget = (
  fragment: CraftCms.Cms.View.HtmlFragment,
  container: HTMLElement,
  active: () => boolean
) => Promise<AppendHtmlDisposer | undefined>;

export const renderHtmlWidget: InjectionKey<RenderHtmlWidget> = Symbol(
  'dashboard-html-widgets'
);

export function provideHtmlWidgets() {
  const assets: AppendHtmlDisposer[] = [];
  let queue = Promise.resolve();
  let mounted = true;

  // Server asset registration is deduplicated across widgets. Initialize them in
  // order and retain their shared styles/scripts for the lifetime of the page.
  provide(renderHtmlWidget, (fragment, container, active) => {
    const render = queue.then(async () => {
      if (!mounted) return;

      assets.push(await appendHeadHtml(fragment.headHtml));
      if (!mounted) return;

      const dispose = active()
        ? await appendElementHtml(fragment.html, container)
        : undefined;
      if (!mounted) {
        dispose?.();
        return;
      }

      if (active()) {
        container.dispatchEvent(
          new CustomEvent('craft:widget-content-ready', {
            bubbles: true,
          })
        );
      }

      let bodyHtml = fragment.bodyHtml;
      if (!active()) {
        const body = document.createElement('template');
        body.innerHTML = bodyHtml;
        bodyHtml = Array.from(body.content.querySelectorAll('script[src]'))
          .map((script) => script.outerHTML)
          .join('');
      }

      assets.push(await appendBodyHtml(bodyHtml));
      if (!mounted || !active()) {
        dispose?.();
        return;
      }

      return dispose;
    });

    queue = render.then(
      () => {},
      () => {}
    );

    return render;
  });

  onUnmounted(() => {
    mounted = false;
    void queue.finally(() => {
      while (assets.length) assets.pop()!();
    });
  });
}
