---
status: accepted
---

# Lazy sessions on Craft site routes

Laravel's `web` group starts and saves a session on every request and sends `laravel_session` and `XSRF-TOKEN` cookies, so shared caches won't store Craft's site responses. We want a response to set cookies only when something needs a session for the visitor.

Routes opt in with `Route::withLazySession()`, a `Router` macro in the style of `allowDuringMaintenance()`. Craft applies it to its site routes, and plugins and host routes, including actions, can opt in too. The macro replaces `StartSession` with Craft's `StartLazySession` on those routes only, using the same `withoutMiddleware` swap that `routes/actions.php` already uses. `StartLazySession` hands each request to the app's own `StartSession` and holds back the session write until the response shows whether the session was used. Requests that arrive with a session cookie behave as they always have. For a request without one, the session is saved and its cookie sent only when something writes to the session or reads the CSRF token. Laravel's own bookkeeping writes (`_token`, flash aging, `_previous.url`, `_database_modified_at`) don't count. The token counts as read when it appears in the response body or headers. Tracking `token()` calls instead would mean replacing Laravel's session store through its protected driver cache, and filtering out `PreventRequestForgery`'s own read on every response. `XSRF-TOKEN` is sent only alongside a saved session. The host app's `web` group and container bindings are untouched.

## Prior art

Lazy sessions are the established answer in other frameworks:

- **Django** saves the session and sends its cookie only when the session was modified ([`SessionMiddleware`](https://github.com/django/django/blob/main/django/contrib/sessions/middleware.py)), and sets the CSRF cookie only when a token was requested ([`get_token()`](https://github.com/django/django/blob/main/django/middleware/csrf.py)).
- **Drupal** starts an anonymous session only when something is stored in it, so that "HTTP proxies [can] cache anonymous page views" ([`SessionManager`](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Session%21SessionManager.php/class/SessionManager/main)).
- **Symfony** starts sessions on demand and makes a response private only when the session was used ([`AbstractSessionListener`](https://github.com/symfony/symfony/blob/7.4/src/Symfony/Component/HttpKernel/EventListener/AbstractSessionListener.php)).

Laravel has no equivalent. Its closest attempt, `Route::static()` ([laravel/framework#60286](https://github.com/laravel/framework/pull/60286)), skipped session middleware and stripped cookies, and was closed unmerged.

## Considered options

- **Strip `Set-Cookie` from responses that look public** (Blitz; Laravel's unmerged `Route::static()`, #60286). Rejected: personal content can end up stored, and real session writes are silently lost.
- **No session at all on site GET routes** (Fast Laravel, Symfony `stateless`). Rejected: logged-in users would see logged-out pages unless templates load everything user-specific with JavaScript.
- **No session only on GET requests without a session cookie**, keeping the full session otherwise. It's less code, but anything such a request writes to the session is silently lost: OAuth redirects (`state` and the intended URL), the intended URL from `{% requireLogin %}`, and plugins that write on GET. Every such writer would have to be excluded or rewritten, so this was rejected in favor of a session that's saved whenever it's written.
- **Bind Craft's middleware over `StartSession` in the container, or edit the host's `web` group.** Rejected: it changes the host app's behavior and doesn't show in `route:list`.

## Consequences

- A page that reads the CSRF token saves the visitor's session and is private.
- Pages viewed without a session no longer record `_previous.url`. When a later request has no `Referer` header, `back()` and `url()->previous()` go to `/` instead of the last page. This affects front-end form failures (`asFailure()`, validation errors) from first-time visitors whose browser or site policy strips the referrer.
- Front-end JS that relied on the `XSRF-TOKEN` cookie sends no token for those visitors.
