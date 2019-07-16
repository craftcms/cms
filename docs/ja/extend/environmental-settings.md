# 環境設定

環境ごとに変更が必要だったり、機密情報を含む可能性があるプラグイン設定は、**環境設定**として実装されるべきです。

環境設定には、環境変数やエイリアスを参照する生の値がセットされ、ランタイムで <api:Craft::parseEnv()> によって解析されます。

これは、環境変数にセットされる `$secretKey` プロパティと、値の解析を担う `getSecretKey()` メソッドのモデルの例です。

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

## バリデーション

環境変数が特別なバリデーションルールを必要としない場合、<api:craft\behaviors\EnvAttributeParserBehavior> を使用して生の値ではなく、パースした値をバリデータに確認させることもできます。

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

## オートサジェスト入力

コントロールパネルで設定値を入力するユーザーに案内するために、設定にオートサジェスト入力を提供できます。

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

`suggestEnvVars` が `true` にセットされている場合、オートサジェスト入力はサジェストを取得するために <api:craft\web\twig\variables\Cp::getEnvSuggestions()> を呼び出します。そして、フォームフィールドの下にヒントを表示し、環境変数にセットできる値をユーザーにアドバイスします。

設定が URL、または、ファイルシステムパスの場合、`suggestAliases` も `true` に設定する必要があります。

```twig{4}
{{ forms.autosuggestField({
    // ...
    suggestEnvVars: true,
    suggestAliases: true
}) }}
```

