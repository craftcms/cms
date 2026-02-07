---
name: testing-guidelines
description: Guidance for writing and updating Craft CMS 6 tests (Pest/Laravel) including element factories, CP URL rules, custom field setup, trait testing, and event assertions. Use when creating or modifying tests, debugging test patterns, or aligning new tests with repository testing standards.
---

# Testing Guidelines

## Overview

Apply Craft CMS 6 testing patterns consistently and avoid common pitfalls around elements, CP URLs, and event assertions. Use the quick rules below, then load the reference file for full examples and code snippets.

## Workflow

1. Identify the test scope (CP URL, element behavior, trait, event, custom field).
2. Follow the matching rule set below.
3. Pull detailed examples from `references/testing-guidelines.md` when needed.
4. Implement tests using Pest and repo conventions.

## Core Rules

- Use `CraftCms\Cms\Cms::config()->cpTrigger` when asserting CP URLs; never hard-code `/admin`.
- Do not instantiate element classes directly with `new` in tests; use factories to ensure database state.
- Prefer factories and element queries over Eloquent models when asserting element behavior.
- For element traits, create minimal test elements that override only what is needed.
- Use Laravel event fakes/listeners to assert dispatch, cancellation, or data changes.

## When to Load References

- Need code samples for entry creation, custom fields, or event tests.
- Need the minimal trait-testing element example.
- Unsure about the exact factory + query pattern for elements.

See `references/testing-guidelines.md` for full examples and snippets.
