# Guide: Setting Up a Localized Site

This guide will walk you through all of the steps that are typically involved in setting up a localized site using Craft’s multi-site feature and translation support.

## Step 1: Defining Your Sites and Languages

The first step to creating localized site is to decide the languages you need to support. After that, create a new Site in Craft for each supported language using the [guide on configuring a multi-site setup in Craft](sites.md).

## Step 2: Update Your Sections

After creating a new site for a language, enable the new site in each Section. In Settings → Sections, go into each section settings you want included in the localized site and enable the site in the Site Settings. Fill out the Entry URI Format (for Channel and Structure sections) or URI (for Single sections) to reflect how you want the URIs structured for that site.

## Step 3: Define Your Translatable Fields

In Settings → Fields, choose the fields you want to have translatable. Under Translation Method, choose "Translate for each language."

Craft will allow you to update this field's content in each entry on a per-language basis.

## Step 4: Update Your Templates

If you have any templates that you only want to serve from a specific site, you can create a new sub-folder in your templates folder, named after your site's handle, and place the templates in there.

For example, if you wanted to give your German site its own homepage template, you might set your templates folder up like this:

```
templates/
├── index.twig      --> default homepage template
└── de/
    └── index.twig  --> German homepage template
```

Use `craft.app.language` to toggle specific parts of your templates, depending on the language:

```twig
{% if craft.app.language == 'de' %}
    <p>I like bread and beer.</p>
{% endif %}
```

You can also take advantage of Craft’s static translation support for strings throughout your templates.

```twig
{{ "Welcome!"|t }}
```

## Step 5: Give your authors access to the sites

As soon as you add an additional site to your Craft installation, Craft will start checking for site permissions whenever users try to edit content. By default, no users or groups have access to any site, so you need to assign them.

When you edit a user group or a user account, you will find a new Sites permissions section, which lists all of your sites. Assign them where appropriate.
