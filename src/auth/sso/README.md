
An example provider

```php
'components' => [
    'sso' => [
        'providers' => [
            'test' => [
                'type' => \craft\auth\oidc\Google::class,
                'settings' => [
                    'clientId' => App::env('GOOGLE_OAUTH_CLIENT_ID'),
                    'clientSecret' => App::env('GOOGLE_OAUTH_CLIENT_SECRET'),
                ]
            ],
//                'saml' => [
//                    'type' => 'modules\auth\providers\Saml',
//                ],
            // etc.
        ],
    ],
]
```
