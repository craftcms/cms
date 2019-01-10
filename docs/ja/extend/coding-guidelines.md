# コーディングガイドライン

Craft や Craft プラグイン向けのコードを書くときには、このガイドラインに従うよう最善を尽くしてください。

[[toc]]

## コードスタイル

- [PSR-1](https://www.php-fig.org/psr/psr-1/) と [PSR-2](https://www.php-fig.org/psr/psr-2/) のコーディング基準に従ってください。
- 短い配列構文（`['foo' => 'bar']`）を使用してください。
- 行の長さにあまり思い悩まないでください。可読性に焦点を当てましょう。
- チェインメソッドの呼び出しは、各行の先頭に `->` 演算子を付けて、独自の行に配置する必要があります。
- 複数行にまたがる条件文には、行の最後に理論演算子（`||`、`&&` など）を付ける必要があります。
- 複数行に渡って連結される文字列は、行の最後に `.` 演算子を付ける必要があります。
- 型変換の後にスペースを置かないでください（`(int)$foo`）。
- `include` / `include_once` / `require` / `require_once` のファイルパスを括弧で囲まないでください。それらはファンクションではありません。

## ベストプラクティス

- 可能な限り、メソッド引数の型を宣言してください。

   ```php
   public function foo(Entry $entry, array $settings)
   ```

- 可能な限り、厳格な比較演算子（`===` および `!==`）を使用してください。

- `is_null($foo)` / `!is_null($bar)` よりむしろ `$foo === null` / `$bar !== null` を使用してください。

- `intval($foo)` / `floatval($bar)` よりむしろ `(int)$foo` / `(float)$bar` を使用してください。

- [in_array()](http://php.net/manual/en/function.in-array.php) の第三引数へ常に `true` / `false` を渡して、型の比較をチェックするかを示します（そして、可能な限り `true` にします）。

- オブジェクトのプロパティがセットされているかをチェックする条件文では、`isset($obj->property)` よりむしろ `$obj->property !== null` を使用してください。

- 配列が空である / ではないをチェックする条件文では、`empty()` / `!empty()` を使用してください。

- 文字列（`'some\nmspace\Foo'`）または <api:yii\base\BaseObject::className()> よりむしろ [::class](http://php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.class) キーワード（`Foo::class`）を使用して、クラス名を参照してください。

- 暗黙的（例：`$array` がまだ定義されていない状態での `$array[] = 'foo'`）よりむしろ明示的（`$array = []`）に配列を初期化してください。

- クラスが拡張されている場合に `static::` が壊れるため、プライベートな static ファンクションを呼び出す際、`static::_foo()` ではなく `self::_foo()` を使用してください。

- `static::CONSTANT`（不必要なオーバーヘッド）よりむしろ `self::CONSTANT` を使用してください。

- 現在のメソッドと同じ名前の親メソッドを呼び出す際のみ、`parent::` キーワードを使用してください。そうでなければ、`$this->` を使用してください。

- クラスのプロパティやメソッドの可視性（`public`、`protected`、または、`private`）は、常に指定してください。

- プライベートなクラスのプロパティ / メソッド名は、アンダースコアではじめます（`private $_foo`）。

- 明示的にクラスプロパティのデフォルト値を `null` セットしないでください（例：`public $foo = null;`）。

- 何かを返すファイルをインクルードする際は、`require_once` または `include_once` よりむしろ `require` または `include` を常に使用してください。

- 短い文字列向けに他の文字列がある文字列ではじまるかをチェックする際は、`strncmp($foo, $bar, $barLength) === 0` よりむしろ `strpos($foo, $bar) === 0` を使用してください。

- 文字列が空かどうかをチェックする際は、`strlen($str) === 0` よりむしろ `$str === ''` を使用してください。

- 可能な場合、ループ内で `array_merge()` の使用を避けてください。

- ループ処理の終了後、foreach ループの参照によって作成された変数を解除してください。

   ```php
    foreach ($array as &$value) {
        // ...
    }
    unset($value);
   ```

- `join()` よりむしろ `implode()` を使用してください。

- needle の位置が必要でない場合、`array_search(...) !== false` よりむしろ `in_array()` を使用してください。

- 単一の `if` 条件で十分な場合、`switch` 文を使用しないでください。

- ダブルクォート（`"`）が不要な場合、シングルクォート（`'`）を使用してください。

- 可能であれば、ショートカット演算子（`+=`、`-=`、`*=`、`/=`、`%=`、`.=` など）を使用してください。

- 可能であれば、ショートカット正規表現パターン（`\d`、`\D`、`\w`、`\W` など）を使用してください。

- ファイルパスを定義する際は、`'/'` よりむしろ `DIRECTORY_SEPARATOR` 定数を使用してください。

::: tip
PhpStorm プラグインの [Php Inspections (EA Extended)](https://plugins.jetbrains.com/idea/plugin/7622-php-inspections-ea-extended-) は、これらのベストプラクティスの問題を見つけて修正するのに役立ちます。
:::

## 名前空間とクラス名

- ベースパスにマップされている既知のベース名前空間があれば、クラスのファイルの場所を完全修飾名で推測できる [PSR-4](https://www.php-fig.org/psr/psr-4/) 仕様に従ってください。
- 名前空間は、すべて小文字であるべきです。
- クラス名は `StudlyCase` にする必要があります。
- ファーストパーティのコードだけが、`craft\` および `pixelandtonic\` 名前空間ルートを使用します。サードパーティプラグインは、ベンダー名とプラグイン名（例：`acme\myplugin\`）を参照する名前空間ルートを使用する必要があります。

## メソッド名

**いかなる引数も受け取らない** Getter メソッド（何かをするのではなく、何かを返すことを主たる目的とするメソッド）は、`get` ではじめます。そして、対応するマジック Getter プロパティを文書化するために、クラスの docblock に対応する `@property` タグがあるべきです。

- `getAuthor()`
- `getIsSystemOn()`
- `getHasFreshContent()`

（省略できるかどうかに関わらず）**1つ、または、複数の引数を受け入れる** Getter メソッドは、「正しいと思う」場合のみ `get` ではじめます。

- `getError($attribute)`
- `hasErrors($attribute = null)`

静的メソッドは、一般的に `get` ではじめるべきではありません。

- `className()`
- `displayName()`

## 型宣言

### 引数の型

可能な限り、すべてのファンクションの引数に PHP 7.0 でサポートされる[引数の型宣言](http://php.net/manual/en/functions.arguments.php#functions.arguments.type-declaration)を使用してください。唯一の例外は、次の通りです。

- [マジックメソッド](http://php.net/manual/en/language.oop5.magic.php)（例：`__toString()`）
- 複数の `null` 以外の値型を受け入れる引数
- 親メソッドで型宣言を持たない、親クラスのメソッドを上書きするメソッド
- インターフェースで必要なメソッドで、インターフェースメソッドに型宣言がないもの

2つの型を受け入れる引数の1つが `null` の場合、引数は `null` 以外の型を型宣言に持ち、デフォルト値を `null` とします。

```php
public function foo(string $bar = null)
```

::: tip
`null` を受け入れる引数の次に必須の引数がある場合も、これを実行します。これは、PHP で `null` を許可しながら引数型を強制する唯一の方法です。
:::

### 戻り値の型

可能な限り、すべてのメソッドに PHP 7.0 でサポートされる[戻り値の型宣言](http://php.net/manual/en/functions.returning-values.php#functions.returning-values.type-declaration)を使用してください。唯一の例外は、次の通りです。

- [マジックメソッド](http://php.net/manual/en/language.oop5.magic.php)（例：`__toString()`）
- 複数の戻り値の型を持つメソッド
- 親メソッドで戻り値の型を持たない、親クラスのメソッドを上書きするメソッド
- インターフェースで必要なメソッドで、インターフェースメソッドに戻り値の型がないもの

## Docblock

- サブクラスメソッドを上書きしたり、インターフェースメソッドを実装したり、docblock へ追加するものがないメソッドは、docblock に `@inheritdoc` だけを持つべきです。
- 適切な大文字、文法、および、句読点を持つ完全な文章を docblock の説明に使用してください。
- `@param` および `@return` タグには、大文字や句読点を使用**しないでください**。
- 型定義では、`boolean` と `integer` の代わりに `bool` と `int` を使用してください。
- 意味をなすとき、配列の型宣言で配列メンバのクラス名を指定してください（`array` よりむしろ `ElementInterface[]`）。
- 現在のクラスのインスタンスを返す連鎖可能なファンクションでは、戻り値の型宣言として `static` を使用するべきです。
- 何も返さないファンクションは、`@return void` を持つべきです。

### インターフェース 対  実装クラス

パブリックサービスメソッド上の `@param`、`@return`、`@var`、`@method` および `@property` タグは、（該当する場合）実装クラスではなくインターフェースを参照します。

```php
// Bad:
/**
 * @param \craft\base\Element $element
 * @param \craft\base\ElementInterface|\craft\base\Element $element
 */

// Better:
/**
 * @param \craft\base\ElementInterface $element
 */
```

インラインの `@var` タグは、インターフェースではなく実装クラスを参照します。

```php
// Bad:
/** @var \craft\base\ElementInterface $element */
/** @var \craft\base\ElementInterface|\craft\base\Element $element */

// Better:
/** @var \craft\base\Element $element */
```

## 制御フロー

### Happy Path

[Happy Path](https://en.wikipedia.org/wiki/Happy_path) を使用してください。すべて期待通りにできた場合、一般的にはメソッドの実行が最後に行き着くところまで処理されるべきです。

```php
// Bad:
if ($condition) {
    // Do stuff

    return true;
}

return false;

// Better:
if (!$condition) {
    return false;
}

// Do stuff

return true;
```

### `if`…`return`…`else`

このようにしないでください。それは意味がなく、一見すると紛らわしいです。

```php
// Bad:
if ($condition) {
    return $foo;
} else {
    return $bar;
}

// Better:
if ($condition) {
    return $foo;
}

return $bar;
```

## コントローラー

### 戻り値の型

リクエストを完了するコントローラーアクションでは、文字列（HTML）、または、Response オブジェクトのいずれかを返す必要があります。

```php
// Bad:
$this->asJson($obj);
$this->renderTemplate($template, $variables);

// Better:
return $this->asJson($obj);
return $this->renderTemplate($template, $variables);
```

### JSON アクション

JSON を返すオプションを持つコントローラーアクションでは、Ajax リクエストの場合ではなく、リクエストが明示的に JSON レスポンスを受け入れる場合に、JSON を返す必要があります。

```php
// Bad:
if (\Craft::$app->getRequest()->getIsAjax()) {
    return $this->asJson([...]);
}

// Better:
if (\Craft::$app->getRequest()->getAcceptsJson()) {
    return $this->asJson([...]);
}
```

JSON *だけを* 返すコントローラーアクションでは、リクエストで JSON を受け入れる必要があります。

```php
$this->requireAcceptsJson();
```

## 例外

- ユーザーエラーの結果として、例外が起こる可能性がある場合、<api:yii\base\UserException> クラス（または、サブクラス）を使用してください。
- <api:yii\base\UserException> の場合のみ、<api:Craft::t()> で例外メッセージを翻訳してください。

## データベースクエリ

- テーブル名は常に `{{%` と `}}`（例：`{{%entries}}`）で囲み、適切に引用されテーブル接頭辞が挿入されるようにします。
- 単一のカラムを参照する場合でも、`'col1, col2'` の代わりに `select()` および `groupBy()` で `['col1', 'col2']` 構文を使用してください。
- `'{{%tablename}}'` の代わりに、`from()` で `['{{%tablename}}']` 構文を使用してください。
- `'col1, col2 desc'` の代わりに、`orderBy()` で `['col1' => SORT_ASC, 'col2' => SORT_DESC]` 構文を使用してください。

### 条件

- テーブル / カラム名や値を自動的に引用するように、可能な限り Yii の[宣言条件構文](api:yii\db\QueryInterface::where())を使用してください。
- 一貫性のために、次のものを使用してください。
   - ``['in', 'col', $values]` の代わりに ['col' => $values]`
   - ``['=', 'col', $value]` の代わりに ['col' => $value]`
   - ``['like', 'col', '%value%', false]` の代わりに ['like', 'col', 'value']`
      *（`%` は `value` が片側にのみ必要な場合を除きます。）*
- `NULL` を検索する場合、`['col' => null]` 構文を使用してください。
- `NOT NULL` を検索する場合、`['not', ['col' => null]]` 構文を使用してください。
- 宣言条件構文が使用できない場合（例えば、しばしば join を使うようなケースのように、条件が値ではなく他のテーブル / カラム名を参照するなど）、100%安全かどうか自信がないすべてのカラム名と値を確実に引用符で囲み、クエリパラメータとして追加する必要があります。

```php
// Bad:
$query->where('foo.thing is null');
$query->innerJoin('{{%bar}} bar', 'bar.fooId = foo.id');

// Better:
$query->where(['foo.thing' => null]);
$query->innerJoin('{{%bar}} bar', '[[bar.fooId]] = [[foo.id]]');
```

## Getter と Setter

Getter および Setter メソッドは、クラスの docblock 内に対応する `@property` タグを持つ必要があります。それによって、PhpStorm のような IDE がマジックプロパティを知ることができます。

```php
/**
 * @property User $author
 */
class Entry
{
    private $_author;

    /**
     * @return User
     */
    public function getAuthor()
    {
        return $this->_author;
    }
}
```

パフォーマンスを少し向上させデバッグを容易にするために、一般的にはマジックプロパティを通すよりむしろ、Getter および Setter メソッドを直接呼び出し続けるべきです。

```php
// Bad:
$oldAuthor = $entry->author;
$entry->author = $newAuthor;

// Better:
$oldAuthor = $entry->getAuthor();
$entry->setAuthor($newAuthor);
```

### App コンポーネントの Getter

App コンポーネントには、App コンポーネントの Getter メソッドである [get()](api:yii\di\ServiceLocator::get()) を直接呼び出す、独自の Getter ファンクションが必要です。

```php
/**
 * @return Entries
 */
public function getEntries()
{
    return $this->get('entries');
}
```

そして、それらをマジックプロパティの代わりに使用する必要があります。

```php
// Bad:
\Craft::$app->entries->saveEntry($entry);

// Better:
\Craft::$app->getEntries()->saveEntry($entry);
```

同じメソッド内で同じ App コンポーネントを複数回参照する場合、ローカル参照をそこに保存します。

```php
// Bad:
\Craft::$app->getEntries()->saveEntry($entry1);
\Craft::$app->getEntries()->saveEntry($entry2);

// Better:
$entriesService = \Craft::$app->getEntries();
$entriesService->saveEntry($entry1);
$entriesService->saveEntry($entry2);
```

