---
paths:
  - 'src/{Auth,Mcp,Providers}/**/*.php'
---

# Auth Mcp Providers

## Preserve host Passport configuration
Craft's MCP integration may add namespaced Passport scopes and a dedicated auth guard, but must not replace a host application's Passport authorization guard, default scopes, or existing scope definitions. Merge additive Passport state after providers boot.
