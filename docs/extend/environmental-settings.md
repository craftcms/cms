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

## Validation

If your environmental settings require special validation rules, you can have the validators check the parsed values rather than the raw values using <api:craft\behaviors\EnvAttributeParserBehavior>.

```php
use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;

class MyModel extends Model
{
    public function behaviors()
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['secretKey'],
            ],
        ];
    }
    
    public function rules()
    {
        return [
            ['secretKey', 'required'],
            ['secretKey', 'string', 'length' => 50],
        ];
    }
    
    // ...
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
    suggestEnvVars: true
}) }}
```

When `suggestEnvVars` is set to `true`, the autosuggest input will call <api:craft\web\twig\variables\Cp::getEnvSuggestions()> to get its suggestions, and a tip will show up below the form field advising the user that they can set the value to an environment variable.

If your setting is for a URL or file system path, you should also set `suggestAliases` to `true`.

```twig{4}
{{ forms.autosuggestField({
    // ...
    suggestEnvVars: true,
    suggestAliases: true
}) }}
suggestAliases: true
```
