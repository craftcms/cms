# RSS フィード

次のテンプレートは、あなたのサイトで RSS 2.0 フィードを提供するために使用できます。`siteDescription` と呼ばれるフィールドを持つ、`globals` というハンドルの[グローバルのセット](../../globals.md)があることを前提としています。

::: tip
ファイル拡張子 `.rss` で終わるテンプレートとして保存すると、Craft はそれを MIME タイプ `application/rss+xml` で配信します。
:::

```twig
<?xml version="1.0"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ siteName }}</title>
        <link>{{ siteUrl }}</link>
        <atom:link href="{{ craft.app.request.absoluteUrl }}" rel="self" type="application/rss+xml" />
        <description>{{ globals.siteDescription }}</description>
        <language>en-us</language>
        <pubDate>{{ now|rss }}</pubDate>
        <lastBuildDate>{{ now|rss }}</lastBuildDate>

        {% for entry in craft.entries.all() %}
            <item>
                <title>{{ entry.title }}</title>
                <link>{{ entry.url }}</link>
                <pubDate>{{ entry.postDate|rss }}</pubDate>
                <author>{{ entry.author }}</author>
                <guid>{{ entry.url }}</guid>
                <description><![CDATA[
                    {{ entry.body }}
                ]]></description>
            </item>
        {% endfor %}
    </channel>
</rss>
```

