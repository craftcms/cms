---
name: artisan-command-authoring
description: Write and review Craft CMS Laravel Artisan commands using repository conventions. Use when adding or refactoring command classes in `src/**/Commands`, porting legacy Yii console controllers, or addressing review feedback on command signatures, aliases, prompts, output formatting, and Laravel service/facade usage.
---

# Artisan Command Authoring

## Overview
Implement or refactor commands so they match Craft CMS 6 Laravel patterns and pass common review checks the first time.

This skill now includes a Laravel 13 Prompts reference in `references/laravel-prompts-reference.md`.

## Workflow
1. Inspect neighboring command classes in the same domain and the domain service provider registration.
2. Define signature, aliases, arguments/options, injected services, and interactive behavior.
3. If the command is interactive, load `references/laravel-prompts-reference.md` and choose from the full prompt surface, not just basic text/confirm/select helpers.
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
- Use Laravel Prompts for interactive input and interactive command UX, including `search()`, `multisearch()`, `autocomplete()`, `form()`, `progress()`, `task()`, `stream()`, `title()`, and `clear()` when they fit.
- Prefer `$this->components->info|warn|error|success|task` for conventional Artisan status output in non-interactive or mixed flows.
- Prompt-side informational helpers (`info()`, `warning()`, `error()`, `alert()`, `note()`, `intro()`, `outro()`, `table()`) are valid when the command is intentionally using a Prompts-driven experience; avoid mixing both styles line-by-line without a reason.
- Avoid ad-hoc `output->write()`/`line()` for status messaging when components or prompt helpers fit.
- Gate interactive prompts with `$this->input->isInteractive()` and provide non-interactive fallbacks.
- Normalize prompt input with `transform:` before `validate:` when trimming or coercion would otherwise leak into validation logic.
- Keep labels, option text, and validation messages short enough for narrow terminals.
- In task closures, do not add unnecessary `return true;` values.
- Do not add manual blank-line spacing after component calls unless behavior requires it.

## When To Load References
- You need any prompt type beyond `text()`, `confirm()`, `select()`, or `multiselect()`.
- You need prompt validation/transform behavior or searchable prompt patterns.
- You need form builder chaining (`form()->...->submit()`) or conditional form steps.
- You need long-running interactive UX (`spin()`, `progress()`, `task()`, `stream()`) or terminal helpers (`title()`, `clear()`).
- You need prompt-specific testing expectations or fallback behavior details.

## Interactive Command Guidance
- Prefer `search()` / `multisearch()` over large static selects when options come from the database or are too numerous to scan.
- Prefer `autocomplete()` or `suggest()` when users benefit from completion but may still need freeform values.
- Prefer `form()` for grouped setup flows where users may need to revisit earlier answers (`CTRL + U` in supported terminals).
- Prefer `task()` for long-running work that benefits from a live log area, status messages, dynamic labels, or partial streamed output.
- Prefer `progress()` for bounded loops and `spin()` for single opaque operations.
- Assume unsupported environments and non-interactive runs still exist even when using Prompts; Laravel configures fallbacks automatically, but command behavior must remain safe and predictable.

## Testing And Fallbacks
- When prompt helpers produce informational output, assert it with prompt-aware expectations such as `expectsPromptsInfo()`, `expectsPromptsWarning()`, `expectsPromptsError()`, `expectsPromptsAlert()`, `expectsPromptsIntro()`, `expectsPromptsOutro()`, and `expectsPromptsTable()`.
- If a command has both interactive and non-interactive branches, test both paths.
- If reviewing custom prompt fallback behavior, prefer Laravel's built-in fallbacks unless the command genuinely needs `Prompt::fallbackWhen(...)` or prompt-class `fallbackUsing(...)` customization.

## Validation
Run at minimum:
```bash
./vendor/bin/pint <touched files>
./vendor/bin/pest --compact <relevant tests>
```
