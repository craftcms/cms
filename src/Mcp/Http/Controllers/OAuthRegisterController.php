<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Controllers;

use CraftCms\Cms\Mcp\OAuth\Metadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use Throwable;

/** @since 6.0.0 */
readonly class OAuthRegisterController
{
    public function __construct(private ClientRepository $clients) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_name' => ['nullable', 'string', 'min:1', 'max:255'],
            'name' => ['nullable', 'string', 'min:1', 'max:255'],
            'redirect_uris' => ['required', 'array', 'min:1'],
            'redirect_uris.*' => ['required', 'string', function (string $attribute, mixed $value, callable $fail): void {
                if (! is_string($value) || ! $this->isValidRedirectUri($value)) {
                    $fail($attribute.' is not a valid URL.');

                    return;
                }

                if (! in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true)) {
                    return;
                }

                if (in_array('*', config('mcp.redirect_domains', ['*']), true)) {
                    return;
                }

                if ($this->hasLocalhostDomain() && $this->isLocalhostUrl($value)) {
                    return;
                }

                if (! Str::startsWith($value, $this->allowedDomains())) {
                    $fail($attribute.' is not a permitted redirect domain.');
                }
            }],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $isRedirectError = collect($errors->keys())->contains(
                fn (string $key): bool => str_starts_with($key, 'redirect_uris'),
            );

            return new JsonResponse([
                'error' => $isRedirectError ? 'invalid_redirect_uri' : 'invalid_client_metadata',
                'error_description' => $errors->first(),
            ], 400);
        }

        $validated = $validator->validated();

        try {
            $client = $this->clients->createAuthorizationCodeGrantClient(
                name: $this->resolveClientName($validated),
                redirectUris: $validated['redirect_uris'],
                confidential: false,
                enableDeviceFlow: false,
            );
            $this->grantMcpScope($client);
        } catch (Throwable $throwable) {
            report($throwable);

            return new JsonResponse([
                'error' => 'server_error',
                'error_description' => 'The client could not be registered.',
            ], 500);
        }

        return new JsonResponse([
            'client_id' => (string) $client->getKey(),
            'grant_types' => $client->getAttribute('grant_types'),
            'response_types' => ['code'],
            'redirect_uris' => $client->getAttribute('redirect_uris'),
            'scope' => Metadata::SCOPE,
            'token_endpoint_auth_method' => 'none',
        ], 201);
    }

    private function grantMcpScope(Model $client): void
    {
        $scopes = $client->refresh()->getAttribute('scopes');

        if (! is_array($scopes) || in_array(Metadata::SCOPE, $scopes, true)) {
            return;
        }

        $client->forceFill(['scopes' => [...$scopes, Metadata::SCOPE]])->save();
    }

    /** @param array<string, mixed> $validated */
    private function resolveClientName(array $validated): string
    {
        return $validated['client_name']
            ?? $validated['name']
            ?? (parse_url((string) ($validated['redirect_uris'][0] ?? ''), PHP_URL_HOST) ?: 'MCP Client');
    }

    private function isValidRedirectUri(string $value): bool
    {
        $scheme = parse_url($value, PHP_URL_SCHEME);

        if (! is_string($scheme)) {
            return false;
        }

        if (in_array($scheme, ['http', 'https'], true)) {
            return Str::isUrl($value, ['http', 'https']);
        }

        /** @var list<string> $allowedSchemes */
        $allowedSchemes = config('mcp.custom_schemes', []);
        $host = parse_url($value, PHP_URL_HOST);

        return in_array($scheme, $allowedSchemes, true) && is_string($host) && $host !== '';
    }

    private function isLocalhostUrl(string $url): bool
    {
        return Str::startsWith($url, [
            'http://localhost:',
            'http://localhost/',
            'http://127.0.0.1:',
            'http://127.0.0.1/',
            'http://[::1]:',
            'http://[::1]/',
        ]);
    }

    /** @return list<string> */
    private function allowedDomains(): array
    {
        /** @var list<string> $allowedDomains */
        $allowedDomains = config('mcp.redirect_domains', []);

        return collect($allowedDomains)
            ->map(fn (string $domain): string => Str::endsWith($domain, '/') ? $domain : "$domain/")
            ->all();
    }

    private function hasLocalhostDomain(): bool
    {
        /** @var list<string> $domains */
        $domains = config('mcp.redirect_domains', []);

        return collect($domains)->contains(fn (string $domain): bool => in_array(
            rtrim(Str::after($domain, '://'), '/'),
            ['localhost', '127.0.0.1', '[::1]'],
            true,
        ));
    }
}
