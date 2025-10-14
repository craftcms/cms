import {LitElement, html, css} from 'lit';
import {customElement, property} from 'lit/decorators.js';

@customElement('cp-system-info')
export default class SystemInfo extends LitElement {
  static override styles = css`
    :host {
      display: block;
      background-clip: padding-box;
    }

    .system-info {
      display: grid;
      grid-template-columns: var(--c-size-icon-xl) auto;
      align-items: center;
      gap: 0.5rem;
      text-decoration: none;
      color: inherit;
    }

    .system-info__icon {
      aspect-ratio: 1;
      border-radius: var(--c-radius-sm);
      display: grid;
      justify-content: center;
      align-items: center;
    }

    svg {
      width: 100%
    }
  `;

  @property()
  href: string | null = null;

  @property()
  name: string | null = null;

  @property()
  target: '_blank' | '_self' = '_blank';

  fallbackIcon() {
    return html`
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 108 108">
        <title>Craft icon</title>
        <path
          fill="currentColor"
          fill-rule="nonzero"
          d="M95 0c7.18 0 13 5.82 13 13v82c0 7.18-5.82 13-13 13H13c-7.18 0-13-5.82-13-13V13C0 5.82 5.82 0 13 0h82Zm-.737 3H13.737A10.692 10.692 0 0 0 3 13.737v80.526A10.692 10.692 0 0 0 13.737 105h80.526C100.11 105 105 100.228 105 94.263V13.737A10.692 10.692 0 0 0 94.263 3Zm-40.94 62.454c3.33 0 6.9-1.339 10.35-4.503l4.758 5.598C63.435 70.687 57.724 73 52.014 73c-11.301 0-18.44-7.668-16.774-18.5C36.906 43.668 46.542 36 57.843 36c5.473 0 10.588 2.19 14.157 6.207l-6.662 5.599c-1.903-2.556-5.115-4.26-8.684-4.26-6.781 0-12.016 4.503-13.086 10.954-.952 6.45 2.855 10.954 9.755 10.954"
        />
      </svg>
    `
  }

  renderBody() {
    return html`
      <div class="system-info__icon">
        <slot name="icon"></slot>
      </div>
      <div class="system-info__name">${this.name}</div>
    `;
  }

  override render() {
    return html`
      <div class="system-info">
        <div class="system-info__icon">
          <slot name="icon">${this.fallbackIcon()}</slot>
        </div>
        <div class="system-info__name">${this.name}</div>
      </div>
    `;
  }
}
