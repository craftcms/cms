# Environmental Settings

Plugin settings that may need to change per-environment, or contain sensitive information, should be implemented as **environmental settings**.

Environmental settings are settings whose raw values may reference an environment variable or alias, and which get parsed by <api:Craft::parseEnv()> at runtime.

Here’s an example model with a `$secretKey` property that may be set to an environment variable, and a `getSecretKey()` method that is responsible for parsing the value.

```php
use Craft;
use craft\base\Model;

class MyModel extends Model
{
    /**
     * @var string the raw secret key (e.g. '$ENV_NAME')
     */
    public $secretKey;
    
    /**
     * @return string the parsed secret key (e.g. 'XXXXXXXXXXX')
     */ 
    public function getSecretKey(): string
    {
        return Craft::parseEnv($this->secretKey);
    }
}
```

## Autosuggest Inputs

To guide users when entering your setting’s value in the Control Panel, give your setting an autosuggest input.

```twig
{% import "_includes/forms" as forms %}

{{ forms.autosuggestField({
    label: "Secret Key"|t('plugin-handle'),
    id: 'secret-key',
    name: 'secretKey',
    value: myModel.secretKey,
    suggestions: craft.cp.getEnvSuggestions()
}) }}
```

`craft.cp.getEnvSuggestions()` is a helper function that returns autosuggest data for all of the available environment variables.

If your setting is for a URL or file system path, you can include [aliases](../config/README.md#aliases) in the autosuggest data as well by passing `includeAliases=true`.

```twig
suggestions: craft.cp.getEnvSuggestions(includeAliases=true)
```
