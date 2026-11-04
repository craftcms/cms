---
title: Always Provide Default Values
impact: HIGH
impactDescription: Prevents undefined behavior, ensures TypeScript safety
tags: initialization, typescript, reliability, defaults
---

## Always Provide Default Values

Every property must have a default value to prevent undefined behavior.

**Incorrect:**

```typescript
@customElement('user-card')
export class UserCard extends LitElement {
  @property({ type: String }) name;
  @property({ type: String }) email;
  @property({ type: Number }) age;
  @property({ type: Boolean }) active;
  @property({ type: Object }) metadata;
  @property({ type: Array }) roles;
}
```

**Correct:**

```typescript
@customElement('user-card')
export class UserCard extends LitElement {
  @property({ type: String }) name = '';
  @property({ type: String }) email = '';
  @property({ type: Number }) age = 0;
  @property({ type: Boolean }) active = false;
  @property({ type: Object }) metadata: Record<string, unknown> = {};
  @property({ type: Array }) roles: string[] = [];
}
```

This ensures:
- No undefined values in templates
- TypeScript strict mode compatibility
- Predictable component initialization
