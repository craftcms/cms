# 日/時フィールド

日付フィールドは date picker を提供します。同様に、オプションで time picker を提供します。

## 設定

日/時フィールドは、日付、時刻、もしくはその両方にするか、お好みで選択できます。

## テンプレート記法

### 日/時フィールドによるエレメントの照会

日/時フィールドを持つ[エレメントを照会](dev/element-queries/README.md)する場合、フィールドのハンドルにちなんで名付けられたクエリパラメータを使用して、日/時フィールドのデータに基づいた結果をフィルタできます。

利用可能な値には、次のものが含まれます。

| 値 | 取得するエレメント
| - | -
| `':empty:'` | 選択された日付を持たない。
| `':notempty:'` | 選択された日付を持つ。
| `'>= 2018-04-01'` | 2018-04-01 以降に選択された日付を持つもの。
| `'< 2018-05-01'` | 2018-05-01 より前に選択された日付を持つもの。
| `['and', '>= 2018-04-04', '< 2018-05-01']` | 2018-04-01 から 2018-05-01 の間に選択された日付を持つもの。
| `['or', '< 2018-04-04', '> 2018-05-01']` | 2018-04-01 より前、または、2018-05-01 より後に選択された日付を持つもの。

```twig
{# Fetch entries with with a selected date in the next month #}
{% set start = now|atom %}
{% set end = now|date_modify('+1 month')|atom %}

{% set entries = craft.entries()
    .<FieldHandle>('and', ">= #{start}", "< #{end}")
    .all() %}
```

::: tip
[atom](dev/filters.md#atom) フィルタは日付を ISO-8601 タイムスタンプに変換します。
:::

### 日/時フィールドデータの操作

テンプレート内で日/時フィールドのエレメントを取得する場合、日/時フィールドのハンドルを利用して、そのデータにアクセスできます。

```twig
{% set value = entry.<FieldHandle> %}
```

それは、選択された日付を表す [DateTime](http://php.net/manual/en/class.datetime.php) オブジェクトを提供します。日付が選択されていない場合、`null` になります。

```twig
{% if entry.<FieldHandle> %}
    Selected date: {{ entry.<FieldHandle>|datetime('short') }}
{% endif %}
```

Craft と Twig は、必要に応じて使用できる日付を操作するためのいくつかの Twig フィルタを提供します。

- [date](dev/filters.md#date)
- [time](dev/filters.md#time)
- [datetime](dev/filters.md#datetime)
- [timestamp](dev/filters.md#timestamp)
- [atom](dev/filters.md#atom)
- [rss](dev/filters.md#rss)
- [date_modify](https://twig.symfony.com/doc/2.x/filters/date_modify.html)

### 投稿フォームで日/時フィールドを保存

日/時フィールドを含める必要がある[投稿フォーム](dev/examples/entry-form.md)がある場合、`date` または `datetime-local` 入力欄を作成できます。

ユーザーに日付だけを選択させたい場合、`date` 入力欄を使用します。

```twig
{% set currentValue = entry is defined and entry.<FieldHandle>
    ? entry.<FieldHandle>|date('Y-m-d', timezone='UTC')
    : '' %}

<input type="date" name="fields[<FieldHandle>]" value="{{ currentValue }}">
```

ユーザーに時刻も選択させたい場合、`datetime-local` 入力欄を使用できます。

```twig
{% set currentValue = entry is defined and entry.<FieldHandle>
    ? entry.<FieldHandle>|date('Y-m-d\\TH:i', timezone='UTC')
    : '' %}

<input type="datetime-local" name="fields[<FieldHandle>]" value="{{ currentValue }}">
```

::: tip
より良いブラウザサポートを[待っている間](https://caniuse.com/#feat=input-datetime)に `date` と `datetime-local` 入力欄を導入するため、[HTML5Forms.js](https://github.com/zoltan-dulac/html5Forms.js) ポリフィルを使用することができます。
:::

#### タイムゾーンのカスタマイズ

デフォルトでは、Craft は日付が UTC で投稿されていると想定します。Craft 3.1.6 から、入力欄の name を `fields[<FieldHandle>][datetime]`、不可視項目の name を `fields[<FieldHandle>][timezone]` とし、[有効な PHP タイムゾーン](http://php.net/manual/en/timezones.php)をセットすることによって、異なるタイムゾーンの日付を投稿できます。

```twig
{% set pt = 'America/Los_Angeles' %}
{% set currentValue = entry is defined and entry.<FieldHandle>
    ? entry.<FieldHandle>|date('Y-m-d\\TH:i', timezone=pt)
    : '' %}

<input type="datetime-local" name="fields[<FieldHandle>][datetime]" value="{{ currentValue }}">
<input type="hidden" name="fields[<FieldHandle>][timezone]" value="{{ pt }}">
```

または、どのタイムゾーンで日付を投稿するかをユーザーに決定させることもできます。

```twig
{% set currentValue = entry is defined and entry.<FieldHandle>
    ? entry.<FieldHandle>|date('Y-m-d\\TH:i', timezone='UTC')
    : '' %}

<input type="datetime-local" name="fields[<FieldHandle>][datetime]" value="{{ currentValue }}">

<select name="fields[<FieldHandle>][timezone]">
    <option value="America/Los_Angeles">Pacific Time</option>
    <option value="UTC">UTC</option>
    <!-- ... -->
</select>
```

#### 日付と時刻を別々に投稿

日付と時刻を別々の HTML 入力欄として投稿したい場合、それらの name を `fields[<FieldHandle>][date]`、および、`fields[<FieldHandle>][time]`にします。

日付入力欄は `YYYY-MM-DD` フォーマット、または、現在のロケールの短縮日付フォーマットのいずれかをセットできます。

時刻入力欄は `HH:MM` フォーマット（24時間表記）、または、現在のロケールの短縮時刻フォーマットのいずれかをセットできます。

::: tip
現在のロケールの日付と時刻のフォーマットを調べるには、テンプレートに次のコードを追加してください。

```twig
日付のフォーマット： <code>{{ craft.app.locale.getDateFormat('short', 'php') }}</code><br>
時刻のフォーマット： <code>{{ craft.app.locale.getTimeFormat('short', 'php') }}</code>
```

次に、PHP の [date()](http://php.net/manual/en/function.date.php) ファンクションのドキュメントを参照し、各フォーマットの文字の意味を確認してください。
:::

