# RSS Feed

The following template can be used to provide a RSS 2.0 feed on your site. It assumes that you have a [global set](../../globals.md) with the handle `globals`, with a field called `siteDescription`.

::: tip
If you save this in a template that ends with a `.rss` file extension, Craft will even serve it with an `application/rss+xml` MIME type.
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

