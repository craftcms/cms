<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuthServer\Http\Controllers;

use CraftCms\Cms\Auth\OAuthServer\OAuthResource;
use CraftCms\Cms\Auth\OAuthServer\OAuthResources;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Registers OAuth clients for a resource, as described by RFC 7591.
 *
 * @since 6.0.0
 */
readonly class RegisterClientController
{
    private const int MAX_CLIENT_NAME_LENGTH = 64;

    public function __construct(
        private ClientRepository $clients,
        private OAuthResources $resources,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $resource = $this->resources->forRequest($request) ?? abort(404);

        $validator = Validator::make($request->all(), [
            'client_name' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
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
        $name = $this->normalizeClientName($this->resolveClientName($validated));

        // Names made up only of formatting characters, marks, or separators (such as variation selectors or tag
        // characters) would render empty
        if (! preg_match('/[^\p{C}\p{M}\p{Z}]/u', $name) || mb_strlen($name) > self::MAX_CLIENT_NAME_LENGTH) {
            return $this->invalidMetadata(sprintf('The client name must be between 1 and %s characters.', self::MAX_CLIENT_NAME_LENGTH));
        }

        if ($this->isReservedClientName($name)) {
            return $this->invalidMetadata('The client name is reserved.');
        }

        try {
            $client = $this->clients->createAuthorizationCodeGrantClient(
                name: $name,
                redirectUris: $validated['redirect_uris'],
                confidential: false,
                enableDeviceFlow: false,
            );
            $this->grantScopes($client, $resource);

            // Set once the client is authorized, so garbage collection can find registered clients that never were.
            // Clients created any other way always have an `updated_at` value.
            Passport::client()->newQuery()->whereKey($client->getKey())->toBase()->update(['updated_at' => null]);
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
            'scope' => implode(' ', $resource->scopes()),
            'token_endpoint_auth_method' => 'none',
        ], 201);
    }

    private function grantScopes(Model $client, OAuthResource $resource): void
    {
        $scopes = $client->refresh()->getAttribute('scopes');

        if (! is_array($scopes)) {
            return;
        }

        $client->forceFill(['scopes' => array_values(array_unique([...$scopes, ...$resource->scopes()]))])->save();
    }

    private function invalidMetadata(string $description): JsonResponse
    {
        return new JsonResponse(['error' => 'invalid_client_metadata', 'error_description' => $description], 400);
    }

    /**
     * Removes invisible, private-use, and unassigned characters from a client name, and collapses its whitespace, so
     * it can’t hide or spoof text on the consent screen.
     */
    private function normalizeClientName(string $name): string
    {
        $name = preg_replace(Str::invisibleCharsPattern(), '', $name) ?? '';

        return Str::squish(Str::stripPrivateAndUnassignedChars($name));
    }

    /**
     * Returns whether a client name could be mistaken for Craft or this site.
     *
     * Anyone can register a client without authenticating, and the consent screen shows its name on this site’s own
     * domain, under its own branding. A client named after Craft or the site would look like a first-party app, so a
     * phishing link to the real authorize URL could trick users into granting it access. Look-alikes, such as “Craft”
     * spelled with a Cyrillic “С” (U+0421), are blocked too. Names that only contain a reserved name are allowed; the
     * redirect host shown on the consent screen covers those.
     */
    private function isReservedClientName(string $name): bool
    {
        $brandNames = [
            Cms::systemName(),
            ...Sites::getAllSites(true)->map(fn (Site $site): string => $site->getName())->all(),
        ];

        // The consent screen translates the system name, and could be shown in any app or site language, regardless
        // of the language of the registration request
        $languages = I18N::getAppLocaleIds()->merge(I18N::getSiteLocaleIds())->unique()->all();
        $reserved = ['Craft', 'Craft CMS'];

        foreach ($brandNames as $brandName) {
            $reserved[] = $brandName;

            foreach ($languages as $language) {
                $reserved[] = t($brandName, category: 'site', locale: $language);
            }
        }

        return array_any(array_unique($reserved), fn ($value) => $value !== '' && Str::areConfusable($name, $value));
    }

    /** @param array<string, mixed> $validated */
    private function resolveClientName(array $validated): string
    {
        return $validated['client_name']
            ?? $validated['name']
            ?? (parse_url((string) ($validated['redirect_uris'][0] ?? ''), PHP_URL_HOST) ?: 'OAuth Client');
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
