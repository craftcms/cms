---
description: "PHP 8.4 and 8.5 language and review guidance"
applyTo: "**/*.php"
---

# PHP 8.5 instructions

This project requires PHP 8.5. Treat all PHP 8.4 and 8.5 syntax below as valid. Do not report it as a parse error, require an older equivalent, or suggest compatibility with PHP 8.3 and below.

## PHP 8.4

### Property hooks

Properties may define `get` and `set` hooks. Hooks may use blocks or expression bodies; an implicit setter parameter is named `$value`.

```php
class User
{
    public string $firstName {
        set => ucfirst(strtolower($value));
    }

    public string $fullName {
        get => "$this->firstName $this->lastName";
    }

    public string $lastName {
        set(string|Stringable $value) {
            $this->lastName = trim((string) $value);
        }
    }
}
```

- A hook that refers to its own property as `$this->property` uses a backing value. Otherwise the property is virtual and has no storage.
- `set => expression` stores the expression result in the backing value. A block setter must assign the backing value itself when storage is intended.
- A setter parameter may be contravariant (wider) than the property type.
- A virtual property supports only the operations for which it defines hooks.
- Hooks are supported on promoted properties, in interfaces, and in abstract classes. Hooks and individual properties may be `final`.
- A child hook can invoke its parent hook with `parent::$property::get()` or `parent::$property::set($value)`.
- `&get` returns by reference. Indirect mutation such as `$object->items[] = $item` requires reference-compatible hook behavior.
- Property hooks are not supported on `static` or `readonly` properties.

### Asymmetric property visibility

Read and write visibility may differ. The first visibility controls reads; `private(set)` or `protected(set)` controls writes.

```php
class Post
{
    public function __construct(
        public private(set) int $id,
        public protected(set) string $title,
    ) {}
}
```

- Only typed properties support asymmetric visibility.
- Set visibility must be the same as or more restrictive than read visibility.
- `private(set)` is valid syntax with no internal spaces and implies a final property.
- References and indirect array mutation use set visibility.
- PHP 8.4 supports this on instance properties; PHP 8.5 also supports it on static properties.

### Other language and core features

- A `new` expression can be dereferenced without parentheses: `new Service()->run()`.
- `#[\Deprecated]` may annotate functions, methods, and class constants and emits `E_USER_DEPRECATED` when used.
- Reflection supports lazy ghosts and proxies through `ReflectionClass::newLazyGhost()`, `newLazyProxy()`, and related lazy-object methods.
- `exit()` and `die()` behave like functions: they can be first-class callables and follow `strict_types` and normal argument coercion.
- `request_parse_body()` parses URL-encoded or multipart bodies for non-POST requests.
- The new `Dom` namespace provides HTML5/WHATWG-compliant DOM classes; legacy `DOM*` classes remain available.
- `BcMath\Number` provides immutable, object-oriented arbitrary-precision numbers with operator support.
- `array_find()`, `array_find_key()`, `array_any()`, and `array_all()` provide native callback-based array searches and predicates.
- `RoundingMode` is accepted by `round()` and adds `PositiveInfinity`, `NegativeInfinity`, `TowardsZero`, and `AwayFromZero`.
- Date/time APIs support creation from timestamps and getting or setting microseconds.
- New multibyte helpers include `mb_trim()`, `mb_ltrim()`, `mb_rtrim()`, `mb_ucfirst()`, and `mb_lcfirst()`.
- New standard helpers include `http_get_last_response_headers()`, `http_clear_last_response_headers()`, `fpow()`, and `grapheme_str_split()`.
- `XMLReader` and `XMLWriter` have `from*()` and `to*()` factories for strings, URIs, streams, and memory.
- PCRE supports variable-length lookbehind and the `r` caseless-restrict modifier.
- PDO supports driver-specific subclasses such as `Pdo\Mysql`, `Pdo\Pgsql`, and `Pdo\Sqlite`, including `PDO::connect()`.

## PHP 8.5

### Pipe operator

`|>` evaluates its left operand, passes that value as the single argument to the callable on the right, and evaluates to the result.

```php
$slug = $title
    |> trim(...)
    |> (fn(string $value): string => str_replace(' ', '-', $value))
    |> strtolower(...);
```

- The right operand may be any expression that evaluates to a callable accepting one non-reference argument.
- First-class callables such as `trim(...)`, closures, and invokable objects are valid.
- Parenthesize an arrow function used as the right operand.
- Each stage receives exactly the previous stage's result. Use a closure when extra arguments are needed.

### Clone with changed properties

`clone` also has function syntax and can be a first-class callable. Its second argument replaces properties after the shallow copy and after `__clone()` runs, including `readonly` properties where set visibility permits it.

```php
$published = clone($draft, [
    'status' => Status::Published,
    'publishedAt' => new DateTimeImmutable(),
]);
```

Replacements run in array iteration order and follow normal assignment rules for visibility, types, hooks, `__set()`, and dynamic properties; only the `readonly` state is temporarily unlocked.

### Constant expressions

Static closures, first-class callables, and casts are valid constant expressions, including in constants, attributes, property defaults, and parameter defaults.

```php
const NORMALIZE = strtolower(...);
const DEFAULT_LIMIT = (int) 10.5;

const VALIDATE = static function (string $value): bool {
    return $value !== '';
};

function map(Closure $callback = strtoupper(...)): array
{
    // ...
}
```

- A constant-expression closure must be `static` and cannot capture variables. Arrow functions are not supported there because they implicitly capture scope.
- A constant-expression first-class callable must directly name a free-standing function or static method.

### Attributes and declarations

- `#[\NoDiscard]` marks a function or method return value as significant. Intentionally ignore it with the new `(void)` cast: `(void) $service->save()`.
- Attributes may annotate global compile-time constants declared with `const`; `#[\Deprecated]` is valid on global constants and traits too.
- `#[\DelayedTargetValidation]` defers invalid-target checks for an attribute until `ReflectionAttribute::newInstance()`.
- `#[\Override]` may annotate properties as well as methods.
- Asymmetric visibility is valid on static properties: `public private(set) static int $count = 0;`.
- Promoted properties may be `final`: `public function __construct(public final string $name) {}`.
- Fatal errors include backtraces by default, controlled by `fatal_error_backtraces`.

### New and expanded APIs

- The always-enabled `Uri` extension provides RFC 3986 URI and WHATWG URL implementations.
- `array_first()` and `array_last()` return the first or last array value, or `null` for an empty array.
- `Closure::getCurrent()` provides the currently executing closure, including for recursive anonymous functions.
- `get_error_handler()` and `get_exception_handler()` return the active handlers.
- `IntlListFormatter` formats localized conjunction, disjunction, and unit lists.
- `FILTER_THROW_ON_FAILURE` makes filter validation failures throw; it cannot be combined with `FILTER_NULL_ON_FAILURE`.
- Cookie and session APIs accept the `partitioned` option.
- `getimagesize()` supports HEIF/HEIC and, with libxml, SVG.
- `Dom\Element::$outerHTML`, `Dom\ParentNode::$children`, `Dom\Element::getElementsByClassName()`, and `Dom\Element::insertAdjacentHTML()` are available.
- `grapheme_levenshtein()` calculates grapheme-aware edit distance.
- `curl_share_init_persistent()` creates persistent cURL share handles for cross-request connection reuse.
- `flock()` supports zlib streams.

## Review rules

- Prefer PHP 8.5 syntax when it makes code clearer; do not request compatibility workarounds for older PHP versions.
- Do not mistake a hook's assignment to its own property for infinite recursion; it accesses the backing value.
- Do not mistake `private(set)`, `protected(set)`, `|>`, `(void)`, `clone($object, [...])`, callable constants, or `new Class()->method()` for invalid syntax.
- Flag real hook issues: accidental virtual properties, missing backing assignments, invalid indirect mutation, incompatible setter types, or writes outside set visibility.
- Flag discarded `#[\NoDiscard]` results unless explicitly cast to `(void)`.
- Flag PHP 8.4/8.5 deprecations, especially implicit nullable parameters (`Type $value = null`), non-canonical casts, semicolon-style `case` statements, backtick execution, `__sleep()`/`__wakeup()`, null array offsets, and incrementing non-numeric strings.
- For exhaustive extension APIs and compatibility changes, consult the official [PHP 8.4 migration guide](https://www.php.net/manual/en/migration84.php) and [PHP 8.5 migration guide](https://www.php.net/manual/en/migration85.php) before claiming an API is unavailable or deprecated.
