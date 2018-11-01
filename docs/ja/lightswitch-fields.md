# Lightswitch フィールド

Lightswitch フィールドでは、「はい」または「いいえ」の答えが必要なとき向けに、トグル入力を提供します。

## フィールド

## テンプレート記法

このフィールドは、データベースから `1` または `0` のいずれかを返します。そのため、`on` の状態にあるかどうかを次のようにテンプレートからテストできます。

```twig
{% if entry.lightswitchFieldHandle %}
    <p>I'm on!</p>
{% else %}
    <p>I'm off.</p>
{% endif %}
```

