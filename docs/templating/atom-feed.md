# Atom Feed

The following template can be used to provide an Atom 1.0 feed on your site. It assumes that you have a [global set](../globals.md) with the handle “globals”, with two fields: “feedAuthorName” and “feedAuthorEmail”.

> {tip} If you save this in a template that ends with a “.atom” file extension, Craft will even serve it with an `application/atom+xml` MIME type.

```twig
<?xml version="1.0"?>
<feed xmlns="http://www.w3.org/2005/Atom">

    <title>{{ siteName }}</title>
    <link href="{{ siteUrl }}" />
    <link type="application/atom+xml" rel="self" href="{{ craft.request.url }}" />
    <updated>{{ now.atom }}</updated>
    <id>{{ craft.request.url }}</id>
    <author>
        <name>{{ globals.feedAuthorName }}</name>
        <email>{{ globals.feedAuthorEmail }}</email>
    </author>

    {% for entry in craft.entries.find() %}
        <entry>
            <id>{{ entry.url }}</id>
            <link type="text/html" rel="alternate" href="{{ entry.url }}" />
            <title>{{ entry.title }}</title>
            <published>{{ entry.postDate.atom }}</published>
            <updated>{{ entry.dateUpdated.atom }}</updated>
            <author>
                <name>{{ entry.author.fullName }}</name>
                <uri>{{ siteUrl }}</uri>
            </author>
            <content type="html"><![CDATA[
                {{ entry.body }}
            ]]></content>
        </entry>
    {% endfor %}
</feed>
```