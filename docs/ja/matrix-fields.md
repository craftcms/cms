# 行列フィールド

行列フィールドでは、1つのフィールド内に複数のコンテンツブロックを作成できます。

## 設定

行列フィールドの設定は、次の通りです。

* **構成** – ここでは、行列フィールドでどのようなブロックタイプが利用可能か、それらのブロックタイプがどのようなサブフィールドを持つ必要があるかを設定します。
* **最大ブロック数** – フィールドに作成できるブロック数の上限（デフォルトは無制限です）

## フィールド

新しいエントリでは、行列フィールドにはボタンのグループが表示されます。フィールド設定で作成したブロックタイプごとに1つのボタンが表示されます。

それらのボタンの1つをクリックすると、新しいブロックが作成されます。ブロックタイプの名前はブロックのタイトルバーに表示され、それぞれのブロックタイプのフィールドにはブロックの本体が存在しているでしょう。

あなたは好きなだけ（または、最大ブロック数の設定で許可されている範囲内で）、行列フィールドへブロックを追加できます。

各ブロックは設定メニューを持ち、そのブロックに対して追加でできることを開示します。

複数のブロックが選択されている場合、選択されたすべてのブロックに対して「折りたたむ / 展開する」「無効 / 有効」および「削除」オプションが適用されます。

メニューオプションの「折りたたむ」 をクリックするか、ブロックのタイトルバーをダブルクリックすることで、行列ブロックを折りたたむことができます。ブロックが折りたたまれている場合、タイトルバーはコンテンツのプレビューを表示するため、それがどんなブロックかを識別できます。

ブロックは、そのブロックのタイトルバーの最後にある「移動」アイコンをドラックして並び替えることもできます。複数のブロックが選択されている場合、選択されたすべてのブロックが一緒に移動します。

## テンプレート記法

### 行列フィールドによるエレメントの照会

行列フィールドを持つ[エレメントを照会](dev/element-queries/README.md)する場合、フィールドのハンドルにちなんで名付けられたクエリパラメータを使用して、行列フィールドのデータに基づいた結果をフィルタできます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエレメント
| - | -
| `':empty:'` | 行列ブロックを持たない。
| `':notempty:'` | 少なくとも1つの行列ブロックを持つ。

```twig
{# Fetch entries with a Matrix block #}
{% set entries = craft.entries()
    .<FieldHandle>(':notempty:')
    .all() %}
```

### 行列フィールドデータの操作

テンプレート内で行列フィールドを出力するには、行列フィールドに対して [for ループ](https://twig.symfony.com/doc/tags/for.html) を使用します。

```twig
{% for block in entry.myMatrixField.all() %}
    ...
{% endfor %}
```

for ループ内に記述されたすべてのコードは、 フィールドに含まれるそれぞれの行列ブロックに対して繰り返されます。定義済みの変数 `block` にセットされる現在のブロックは、<api:craft\elements\MatrixBlock> モデルになります。

次に、4つのブロックタイプ（見出し、テキスト、画像、および、引用）を持つ行列フィールドのテンプレートの実例を示します。`block.type` （<api:craft\elements\MatrixBlock::getType()>）をチェックすることによって、現在のブロックタイプのハンドルを確認できます。

```twig
{% for block in entry.myMatrixField.all() %}

    {% if block.type == "heading" %}

        <h3>{{ block.heading }}</h3>

    {% elseif block.type == "text" %}

        {{ block.text|markdown }}

    {% elseif block.type == "image" %}

        {% set image = block.image.one() %}
        {% if image %}
            <img src="{{ image.getUrl('thumb') }}" width="{{ image.getWidth('thumb') }}" height="{{ image.getHeight('thumb') }}" alt="{{ image.title }}">
        {% endif %}

    {% elseif block.type == "quote" %}

        <blockquote>
            <p>{{ block.quote }}</p>
            <cite>– {{ block.cite }}</cite>
        </blockquote>

    {% endif %}

{% endfor %}
```

> このコードは [switch](dev/tags/switch.md) タグを利用して、簡略化できます。

### ブロックタイプのフィルタリング

特定のタイプのブロックだけを出力したい場合、行列フィールドに「type」フィルタを追加します。

```twig
{% for block in entry.myMatrixField.type('text').all() %}
    {{ block.text|markdown }}
{% endfor %}
```

あなたが望むなら、複数のブロックタイプを渡すことができます。

```twig
{% for block in entry.myMatrixField.type('text, heading').all() %}
    {% if block.type == "heading" %}
        <h3>{{ block.heading }}</h3>
    {% else %}
        {{ block.text|markdown }}
    {% endif %}
{% endfor %}
```

### リミットの調整

デフォルトでは、行列フィールドは最初の100ブロックを返します。変更するには `limit` パラメータで上書きします。

```twig
{% for block in entry.myMatrixField.limit(5) %}
```

100以上のブロックがあり、それらすべてのブロックを返したいと考えるなら、パラメータを `null` にセットすることもできます。

```twig
{% for block in entry.myMatrixField.limit(null) %}
```

### ブロックの総数の取得

[length フィルタ](https://twig.symfony.com/doc/filters/length.html)を利用して、ブロックの総数を取得できます。

```twig
{{ entry.myMatrixField|length }}
```

## 関連項目

* [エレメントクエリ](dev/element-queries/README.md)
* <api:craft\elements\MatrixBlock>

