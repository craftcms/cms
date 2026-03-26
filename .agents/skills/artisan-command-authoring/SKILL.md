---
name: artisan-command-authoring
description: Write and review Craft CMS Laravel Artisan commands using repository conventions. Use when adding or refactoring command classes in `src/**/Commands`, porting legacy Yii console controllers, or addressing review feedback on command signatures, aliases, prompts, output formatting, and Laravel service/facade usage.
---

# Artisan Command Authoring

## Overview
Implement or refactor commands so they match Craft CMS 6 Laravel patterns and pass common review checks the first time.

This skill now includes a full Laravel Prompts reference in `references/laravel-prompts-reference.md`.

## Workflow
1. Inspect neighboring command classes in the same domain and the domain service provider registration.
2. Define signature, aliases, arguments/options, injected services, and interactive behavior.
3. If the command is interactive, load `references/laravel-prompts-reference.md` and pick the appropriate prompt helpers.
4. Implement using Laravel-first APIs (services/facades/components/prompts), then preserve required backward-compatible aliases.
5. Run format + targeted tests and address findings.

## Command Construction Rules
- Use `final class ... extends Command` plus `use CraftCommand;`.
- Define `protected $signature`, `protected $description`, and `protected $aliases` explicitly.
- Read CLI inputs via `$this->argument()` / `$this->option()` inside `handle()`.
- Inject services into `handle()` (or constructor when needed) instead of pulling from globals.
- Prefer Laravel APIs over legacy Yii/Craft app APIs where equivalent exists.
- Keep compatibility aliases when replacing legacy commands.

## Output And Prompt Rules
- Prefer `$this->components->info|warn|error|success|task` for user-facing output.
- Avoid ad-hoc `output->write()`/`line()` for status messaging when components fit.
- Use Laravel Prompts helpers for interactive input. See `references/laravel-prompts-reference.md` for the full helper and form-builder surface.
- Gate interactive prompts with `$this->input->isInteractive()` and provide non-interactive fallbacks.
- In task closures, do not add unnecessary `return true;` values.
- Do not add manual blank-line spacing after component calls unless behavior requires it.

## When To Load References
- You need any prompt type beyond `text()`, `confirm()`, `select()`, or `multiselect()`.
- You need prompt validation/transform behavior or searchable prompt patterns.
- You need form builder chaining (`form()->...->submit()`) or conditional form steps.
- You need prompt-specific testing expectations or fallback behavior details.

## Validation
Run at minimum:
```bash
./vendor/bin/pint <touched files>
./vendor/bin/pest --compact <relevant tests>
```
