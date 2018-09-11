# 日/時フィールド

日付フィールドは date picker を提供します。同様に、オプションで time picker を提供します。

## 設定

日/時フィールドは、日付、時間、もしくはその両方にするか、お好みで選択できます。

## フィールド

日/時フィールドでは、設定に応じて date picker、time picker、または両方が表示されます。

日付と時刻は、いずれもユーザーが優先するロケールに応じてフォーマットされます。それらは、サイトのタイムゾーンで表示されますが、Craft の他のすべての日付と同様に UTC で保存されます。

## テンプレート記法

テンプレート内で日付フィールドを呼び出すと、選択された日付の [DateTime](http://php.net/manual/en/class.datetime.php) オブジェクトが返ります。フィールドに値がない場合、`null` を返します。

```twig
{% if user.birthday %}
    {{ user.name }}’s birthday is: {{ user.birthday|date('M j, Y') }}
{% endif %}
```

