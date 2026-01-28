import {LitElement, html} from 'lit';
import {customElement} from 'lit/decorators.js';

@customElement('cp-queue-indicator')
class CpQueueIndicator extends LitElement {
  protected override render() {
    return html`
      <craft-nav-item>
        <span slot="icon">
          Ind
        </span>
        Queue
      </craft-nav-item>
    `
  }
}
