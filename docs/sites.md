# Sites

In Craft 3 you can host multiple websites in a single Craft installation.

You can define one or more sites at different domains, using a different set of templates, and different versions of entry content. 

The multi-site feature in Craft is for sites with the same publishing team. You manage the multi-site content at the entry level, with the ability to enable Sections you want included in a site.

::: tip
In some cases, especially using Valet for local development, you can run into issues serving multiple sites if the primary site URL is set with @web. We recommend creating a site alias instead.
:::

## Creating a Site

Every Craft installation starts with one default site. The site name is what you defined at time of installation, and the handle is `default`.

You add additional sites using the Sites settings in Settings → Sites.

Each site has the following attributes:

* Group
* Name
* Handle
* Language
* Is Primary Site?
* Base URL


### Site Groups

Site Groups allow you to organize your sites together by commonality, like language or site type.

Craft creates the first Site Group for you – named after the default site – and assigns the default site to that group.

Similar to Field Groups, Site Groups are for organization only.

You can access the current site's group information using: 

```twig
Site ID:            {{ currentSite.id }}
Site Handle:        {{ currentSite.handle }}
Site Name:          {{ currentSite.name }}
Site Language:      {{ currentSite.language }}
Is Primary Site?:   {{ currentSite.primary }}
Base URL:           {{ currentSite.baseUrl }}
```


### Language

Choosing the language for the site tells Craft the language to use when formatting dates, times, and numbers, and translating static messages.

In your templates, you can also access the language setting via `craft.app.language`. You can use this in a conditional:

```twig
{% if craft.app.language == 'de' %}
    <p>Guten Tag!</p>
{% endif %}
```

Or as a way to automatically include the proper template for each language:

```twig
{% include '_share/footer-' ~ craft.app.language %}
```

where your template name would be, for example, `_share/footer-de`. 


### Primary Site

Craft sets the default site as the Primary site, meaning Craft will load it by default on the front end, if it is unable to determine which site to load. If you only have one site then you cannot disable it as the Primary site. 

You can change the Primary site once you create additional sites. Craft will automatically toggle the current Primary site.

### Site URL

Each site has a Base URL, which Craft uses as the starting point when generating dynamic links to entries and other site content.

Sites can all share the same domain name, such as `https://craftcms.com` and `https//craftcms.com/beta`, or they can have different domains, like `https://www.craftcms.com` and `https://beta.craftcms.com` .

If you want to create a site that will live at a completely different domain name, just make sure that its DNS record is pointing to your server, and make sure the web server is configured to point traffic for that domain to your `web/` directory.

::: tip
If you have multiple sites using different root domains like `https://site-a.com` and `https://site-b.com`, with the way Craft’s [license enforcements works](https://craftcms.com/support/license-enforcement), you’ll want to pick one of the domains to access the Craft Control Panel from for _all_ of the sites.
:::

::: tip
Craft doesn’t require you to create additional `web/` directories for new sites, though it’s fine if you need to.
:::


## Propagating Entries Across All Enabled Sites

In the settings for each Channel Section is an option to propagate entries in that section across all sites. This is enabled by default, and is the only option for Single and Structure sections.

When enabled, Craft will create the new entry in each site enabled for that section using the submitted content.

If you would like the section's content to be separate then disable this option for that section.

## Guide: Setting Up a New Site

In this short guide we'll walk through the steps of setting up a new site in Craft. This guide assumes you already have Craft installed and the default site setup and configured.

### Step 1: Create the Site in Settings

The first step is to create the new site in the Settings of your Craft installation.

1. Go to Settings → Sites and click the New Site button.
2. Choose the group your site should belong to using the drop-down. The group selection won't have any impact on your site's functionality.
3. Give your site a name. Craft uses the site name in the Control Panel and you can also display it in your templates using `{{ siteName }}`.
4. Based on the Site name, Craft will generate a Site Handle. You can edit the Handle if you'd like. You will use the Site Handle to refer to this site in the templates.
5. Choose the language for this site (see above for more information on how you can use languages).
6. If this site should be the Primary Site, toggle the Is Primary Site? to enable it.
7. Check the box for "This site has its own base URL" and then put in the Base URL. For our example it'll be `https://beta.craftcms.com`.
8. Save the new site.

### Step 2: Create Template Directories

Create the template directories and templates for your new site. 

We recommend you have template directories named after the sites handles (e.g. `templates/default` and `templates/beta`). You store the site-specific templates in each site template directory.

### Step 3: Update the Site Sections and Fields

1. Go into each Section that you want to be available in the new site and enable the site using the Site Settings table.
2. Define the Entry URI Format, Template, and Status for the new site in each Section.
3. Choose whether you want to propagate the entries across all sites. If checked, Craft will create a new entry in every site in the system. If the option is unchecked, Craft will only save the new entry to the site you have currently selected.
 
### Step 4: Define Translation Method of Fields

By default, your custom fields will store values on a per-site basis. If you have a Body field, each site can store its only content in that field. 

If your site has a different language than your default language then you will need to set each field as Translatable (by site, by language, or site group).

To set the Translation Method, go into each field you'd like to translate and choose the appropriate option under Translation Method.

### Step 5: Test Your Settings

Using new or existing entries, test that the Section, Field, and Translation Method settings work as you expect.

### Step 6: Check Your Asset Volumes Settings

If you have any local asset volumes, you will need to make sure those assets are available from each of your sites.

* The File System Path settings should be relative (`uploads/images/`).
* The URL settings should be relative (`/images`)

### Step 7: Configure Your Web Server and DNS

1. Configure your web server so the domain (e.g. `beta.craftcms.com`) points at the `web` directory. Craft will automatically detect which site the browser is requesting.
2. Update your DNS records so the domain points at the web server.
