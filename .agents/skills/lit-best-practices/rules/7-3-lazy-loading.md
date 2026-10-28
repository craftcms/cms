---
title: Lazy Load Heavy Dependencies
impact: HIGH
impactDescription: Reduces initial bundle size, improves time-to-interactive
tags: performance, loading, code-splitting, lazy-loading
---

## Lazy Load Heavy Dependencies

Dynamically import heavy dependencies only when needed.

**Incorrect:**

```typescript
// Heavy library loaded at module parse time
import { renderChart } from './heavy-chart-library.js';
import { marked } from 'marked';
import hljs from 'highlight.js';

@customElement('chart-widget')
export class ChartWidget extends LitElement {
  @property({ type: Array }) data: DataPoint[] = [];
  @property({ type: Boolean }) visible = false;
  
  render() {
    if (!this.visible) {
      return html`<div class="placeholder">Chart hidden</div>`;
    }
    return html`<div>${renderChart(this.data)}</div>`;
  }
}
```

**Correct:**

```typescript
import { until } from 'lit/directives/until.js';

@customElement('chart-widget')
export class ChartWidget extends LitElement {
  @property({ type: Array }) data: DataPoint[] = [];
  @property({ type: Boolean }) visible = false;
  
  private _chartPromise: Promise<typeof import('./heavy-chart-library.js')> | null = null;
  
  render() {
    if (!this.visible) {
      return html`<div class="placeholder">Chart hidden</div>`;
    }
    
    // Lazy load only when visible
    if (!this._chartPromise) {
      this._chartPromise = import('./heavy-chart-library.js');
    }
    
    return html`
      ${until(
        this._chartPromise.then(module => 
          module.renderChart(this.data)
        ),
        html`<div class="loading">Loading chart...</div>`
      )}
    `;
  }
}
```

**Pattern for conditional heavy components:**

```typescript
@customElement('markdown-preview')
export class MarkdownPreview extends LitElement {
  @property({ type: String }) content = '';
  @state() private _html = '';
  @state() private _loading = false;
  
  private _markedPromise?: Promise<typeof import('marked')>;
  
  async updated(changedProperties: PropertyValues) {
    if (changedProperties.has('content') && this.content) {
      this._loading = true;
      
      // Lazy load marked only when needed
      if (!this._markedPromise) {
        this._markedPromise = import('marked');
      }
      
      const { marked } = await this._markedPromise;
      this._html = await marked.parse(this.content);
      this._loading = false;
    }
  }
  
  render() {
    if (this._loading) {
      return html`<div class="loading">Rendering...</div>`;
    }
    return html`<div .innerHTML=${this._html}></div>`;
  }
}
```

**Benefits:**
- Initial bundle stays small
- Heavy code loads on-demand
- Better caching (heavy modules cached separately)
