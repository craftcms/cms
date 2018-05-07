The Pieces of Craft
===================

Before you get started using Craft CMS to build your website, let's first talk about the pieces of Craft. These are a large components that you'll use to implement your website's content.

## Sections

Sections are how we organize our content or data in Craft. Let's say you have a cocktail lounge website with various types of content: news, events, and recipes.

There are three different Section Types in Craft: Channels, Structures, and Singles. Each of these types allows you to store your content with a slightly different set of features.

To review the specifics of each type, check out the [Sections and Entries](sections-and-entries.md) page of this documentation.

## Entries

Inside of the Sections are Entries. Entries are your individual pieces of content (like a cocktail recipe). A section can have one or many entries. The entries are typically inputted via the Entry Form in the Craft Control Panel.

Each entry can only belong to one Section.

## Fields

We use Fields in Craft to define the different content types that are stored in an Entry. Craft provides some fields automatically (Title, Slug, Post Date, and more) but we can also define our own fields so we can mold Craft _around_ our content.

Let's say we are working on our cocktail lounge website and want to build out the Recipes section for the house drinks. 

We've already created the Section called "Recipes" to hold our entries. Now we need to create the fields to hold our content within that Section.

Our recipes are pretty simple but we do need to define some Fields:

* Recipe Image
* Recipe Snapshot (short description)
* Recipe Content (ingredients and instructions)

We could get even more detailed with the Recipe Content but this will be fine for now.


## Templates

We use Templates in Craft to output our website design and content. You can think of a template as a HTML document.

Craft is agnostic to your website's layout. It doesn't use themes or impose any content layout or structure. You can use your own HTML/CSS/JS layout. 

Craft uses the [Twig templating engine](https://twig.symfony.com) to make it really easy for you to bring the website templates to life using the data you’ve saved in Craft.

Twig is fantastic because it is flexible and fast. The Twig website has a solid overview of what Twig is and how you can use it in your HTML templates.

Twig isn’t specific to Craft; it’s developed and maintained by the Symfony project.

## More Information

For more depth on each piece, click through to the Core Concepts section of the documentation.

