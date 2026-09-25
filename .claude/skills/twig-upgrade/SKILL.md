---
name: twig-upgrade
description: Checklist for updating the twig/twig dependency. Lists the Craft classes and methods that copy or mirror Twig core code, what to diff each one against, and how to verify the upgrade. Use when bumping twig/twig in composer.json, or when reviewing whether Craft's Twig integration is in sync with upstream.
---

# Updating Twig

Several Craft classes copy, fork, or depend on Twig internals. When `twig/twig` is bumped, each of them must be compared against the new upstream code, or Craft silently drifts from Twig's behavior (including sandbox security fixes).

## 1. See what changed upstream

Clone Twig into the scratchpad and diff the two versions for the files listed below:

```sh
git clone https://github.com/twigphp/Twig.git <scratchpad>/twig
cd <scratchpad>/twig
git diff v<OLD> v<NEW> --stat -- src/
git diff v<OLD> v<NEW> -- src/Node/Expression/GetAttrExpression.php src/Sandbox/ src/NodeVisitor/SandboxNodeVisitor.php
```

Also read `CHANGELOG` between the two versions. Look especially for entries mentioning the sandbox, `GetAttrExpression`, `getAttribute()`, deprecations, or security fixes.

## 2. Files to check

### Direct copies (highest risk)

| Craft file | Upstream counterpart | Notes |
|---|---|---|
| `src/web/twig/nodes/GetAttrNode.php` — `compile()` | `src/Node/Expression/GetAttrExpression.php` — `compile()` | Line-for-line port. Differences are marked with `// DIFF` comments. The main intended difference is calling `craft\helpers\Template::attribute()` instead of `CoreExtension::getAttribute()`. `compileArrayKey()` also intentionally coerces every `Stringable` key to a string, to match `Template::attribute()`, rather than only for arrays, `ArrayObject`, and `ArrayIterator` like upstream (since Twig 3.29). Private upstream helpers (`compileArrayKey()`, `isShortCircuited()`, `markAsShortCircuited()`, `getVarName()`) are reimplemented here and must be re-synced too. Port every upstream change to `compile()` or its helpers. |
| `src/web/twig/SecurityPolicy.php` | `src/Sandbox/SecurityPolicy.php` (a `final` class, so Craft forks it) | `checkSecurity()`, `checkMethodAllowed()`, `checkPropertyAllowed()`, the error messages, and the constructor/setter structure mirror upstream. Craft adds `allowedClasses`, the `AllowedInSandbox` attribute, the `AllowableInSandbox` interface, and a getter fallback for `yii\base\BaseObject` properties. Also check `src/Sandbox/SecurityPolicyInterface.php` for signature changes. See the sandbox notes below. |
| `src/web/twig/nodes/expressions/binaries/HasSomeBinary.php`, `HasEveryBinary.php` | `src/Node/Expression/Binary/HasSomeBinary.php`, `HasEveryBinary.php` | Identical except they compile to `craft\web\twig\Extension::arraySome()`/`arrayEvery()`. Those methods in `src/web/twig/Extension.php` delegate to `CoreExtension::checkArrow()` and `CoreExtension::arraySome()`/`arrayEvery()`, so check those signatures too. |
| `src/web/twig/tokenparsers/DeprecatedTokenParser.php`, `nodes/DeprecatedNode.php` | `src/TokenParser/DeprecatedTokenParser.php`, `src/Node/DeprecatedNode.php` | Derived from an older upstream version. The node logs to Craft's deprecator instead of calling `trigger_deprecation()`. Upstream's `package`/`version` options were never ported. Only update if upstream changes the core parsing. |

### Code that depends on Twig internals

| Craft file | What to check |
|---|---|
| `src/web/twig/nodevisitors/GetAttrAdjuster.php` | Swaps every `GetAttrExpression` for `GetAttrNode` and copies its nodes and attributes **explicitly**. If upstream adds an attribute to `GetAttrExpression` (or sets one on it from elsewhere, such as `sandboxed_function_name`/`sandboxed_function` from `ExpressionParser/Infix/FunctionExpressionParser.php`), it must be copied here too, or it is silently dropped. Grep the new version for `setAttribute(` calls that target `GetAttrExpression` nodes. |
| `src/helpers/Template.php` — `attribute()` | Wraps `CoreExtension::getAttribute()`. Compare its signature with upstream's. The `BaseObject` property branch bypasses `getAttribute()`, so compare it against upstream's sandbox checks (`checkPropertyAllowed()`, `ensureToStringAllowed()` for `Stringable` items). Its twigphp/Twig#4701 workaround coerces every `Stringable` key to a string for non-method calls. Upstream only does that for arrays, `ArrayObject`, and `ArrayIterator`, and passes the object through for other `ArrayAccess` implementations, which breaks Collections, so keep the workaround unless upstream's behavior changes. |
| `src/web/twig/nodes/FallbackNameExpression.php` | Mirrors `src/Node/Expression/Variable/ContextVariable.php`/`NameExpression.php`'s `compile()`. Deprecated since 5.9.15; only keep it compiling. |
| `src/web/twig/nodes/NavNode.php` | Extends `src/Node/ForNode.php` and calls `parent::compile()`. Check `ForNode`'s constructor signature and the loop variables it compiles. |
| `src/web/twig/Extension.php` | Many filters and functions override core ones but delegate to `CoreExtension` static methods (`sort`, `reduce`, `map`, `filter`, `merge`, `length`, `testEmpty`, `checkArrow`, `formatDate`, `convertDate`, …). Check that those still exist with the same signatures. `capitalizeFilter()` copies `CoreExtension::capitalize()`'s logic. Also compare the `getFilters()`/`getFunctions()`/`getTests()` options (e.g. `needs_environment`, `needs_charset`, `needs_is_sandboxed`, `is_safe`) against the core definitions they override. |
| `src/base/Element.php` — `getIterator()` | Skips custom fields when called from the sandbox's `__toString()` checks, to avoid infinite recursion (#19004). It detects that by checking `debug_backtrace()` for a specific class and method (`Twig\Sandbox\SecurityChecker::doEnsureToStringAllowed()` since Twig 3.29; `SandboxExtension` before that). If upstream renames or moves that method, the check silently stops matching. `tests/unit/base/ElementTest.php` covers it. |
| `src/web/View.php` — `sandbox()` | Turns sandboxing on and off for Craft's shared environment through `SandboxExtension::getChecker()->setSandboxed()`, because `enableSandbox()`/`disableSandbox()` are deprecated since Twig 3.29. `getChecker()` and `SecurityChecker` are `@internal`, so check that they still exist. Twig's replacement, `Twig\Sandbox\Sandbox`, requires a dedicated environment and a strict policy, so it doesn't fit Craft's approach yet. |
| `src/web/twig/tokenparsers/NavTokenParser.php` | Mirrors `src/TokenParser/ForTokenParser.php`'s handling of loop targets. |
| `src/config/twig-sandbox.php`, `src/web/View.php` (`createTwig()`) | If upstream adds allowlist types or constructor arguments to the sandbox, decide whether Craft's policy and config should support them. |

## 3. Sandbox notes

- Twig's `SandboxExtension::checkSecurity()` uses reflection to check how many parameters the policy's `checkSecurity()` declares, and triggers a deprecation if it declares fewer than upstream expects. Keep `SecurityPolicy::checkSecurity()`'s parameter list in sync, even for arguments Craft ignores (such as `array $tests`).
- Upstream often loosens things temporarily with "X is always allowed in sandboxes, but won't be in 4.0" deprecations. Don't copy these into Craft's policy without checking what Craft's policy currently does. If Craft already rejects something, copying upstream's deprecate-but-allow branch loosens the sandbox. For example, Twig 3.27 made `parent()`/`block()`/`attribute()` reportable to the policy, and Craft rejects them unless they're in `allowedFunctions`.
- Craft's node visitors and Twig's `SandboxNodeVisitor` all use priority `0`, and Craft's extension is registered first, so Craft's visitors see (and can replace) nodes before the sandbox does. Any node replacement must preserve the attributes the sandbox reads.

## 4. Verify

- **Tests:** run `vendor/bin/codecept run unit web/twig`. This needs the test database named in `tests/.env` to exist.
- **Static analysis and coding standards:** run `vendor/bin/phpstan analyse` and `vendor/bin/ecs check` on the changed files.
- **Compiled templates:** compiled templates are cached by template source, not by Craft's node or visitor code. After changing `GetAttrNode`, `GetAttrAdjuster`, or any other compile-time code, clear `tests/_craft/storage/runtime/compiled_templates/` before running tests, or stale compiled templates will hide the change.
- **Strict variables:** `GetAttrNode`'s optimized array path only runs when strict variables are **off** (Dev Mode disabled). The test config enables Dev Mode, so cover that path with a standalone `Twig\Environment` created with `'strict_variables' => false` and `GetAttrAdjuster` registered (see `tests/unit/web/twig/GetAttrNodeTest.php`).
- **Template deprecations:** compile every template under `src/templates/` with a temporary test that records `E_USER_DEPRECATED` errors, and check for deprecations introduced by the new version. `exception.twig` fails to compile because it uses a `renderSourceCode` filter that Craft doesn't register. That's a pre-existing issue, not a Twig regression.
- **Sandbox:** exercise it with `View::renderSandboxedString()` after setting `enableTwigSandbox = true` (see `tests/unit/web/twig/SecurityPolicyTest.php`).
