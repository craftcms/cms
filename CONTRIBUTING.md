# Contributing to Craft CMS

Thanks for taking the time to contribute! We really appreciate it.

The following is a set of guidelines for contributing to Craft CMS and its first party plugins, which are hosted in the [Craft CMS organization](https://github.com/craftcms) on GitHub. These are just guidelines, not rules. Use your best judgment, and feel free to propose changes to this document in an issue or pull request.

#### Table of Contents

- [What to Know Before You Contribute](#what-to-know-before-you-contribute)
- [Security Disclosures](#security-disclosures)
- [Bug Reports & Feature Requests](#bug-reports--feature-requests)
- [Documentation Edits](#documentation-edits)
- [Control Panel Translations](#control-panel-translations)
- [Core Enhancements](#core-enhancements)
- [Pull Requests](#pull-requests)

## What to Know Before You Contribute

### Craft isn’t FOSS

Let’s get one thing out of the way: Craft CMS is :smiling_imp:**proprietary software**:smiling_imp:. Everything in this repo, including community-contributed code, is the property of Pixel & Tonic.

That comes with some limitations on what you can do with the code:

- You can’t change anything related to licensing, purchasing, edition/feature-targeting, or anything else that could mess with our alcohol budget.
- You can’t publicly maintain a long-term fork of Craft. There is only One True Craft.

### Code of Conduct

Please take a couple minutes to read through Craft’s [code of conduct](https://craftcms.com/docs/4.x/coc.html). By participating here, you are expected to uphold this code. Please report unacceptable behavior to us from [craftcms.com/contact](https://craftcms.com/contact).

## Security Disclosures

If you discover a security vulnerability, please review our [Security Policy](https://github.com/craftcms/.github/blob/master/SECURITY.md), then report the issue directly to us from [craftcms.com/contact](https://craftcms.com/contact). We will review and respond privately via email.

## Bug Reports & Feature Requests

Before submitting bug reports and feature requests, please search through [open issues](https://github.com/craftcms/cms/issues) to see if yours has already been filed.

If you do find a similar issue, upvote it by adding a :thumbsup: [reaction](https://github.com/blog/2119-add-reactions-to-pull-requests-issues-and-comments). Only leave a comment if you have relevant information to add.

If no one has filed the issue yet, [submit a new one](https://github.com/craftcms/cms/issues/new). Please include a clear description of the issue, and as much relevant information as possible, including a code sample demonstrating the the issue.

## Documentation Edits

Craft’s documentation lives in the [https://github.com/craftcms/docs](https://github.com/craftcms/docs) repository. Improvements or corrections to them can be submitted as a pull request.

The documentation is powered by [VuePress](https://vuepress.vuejs.org/). To preview documentation changes before committing them, clone the docs repo and from your terminal, run these commands:

```bash
# Install npm dependencies
npm install

# Build + run the VuePress dev server
npm run docs:dev
```

Once the VuePress dev server is up and running, you’ll be able to view the docs at `http://localhost:8000/`. Changes you make to the `.md` files will automatically trigger a live reload of the pages in the browser.

## Control Panel Translations

We manage Craft’s Control Panel translations with [Crowdin](https://crowdin.com/project/craft-cms).

If you want to help improve Craft’s translations, [sign up to be a translator](https://crwd.in/craft-cms), or you can submit a pull request directly to the [src/translations/](https://github.com/craftcms/cms/tree/develop/src/translations) folder if you prefer. 

## Core Enhancements

If you would like to work on a new core feature or improvement, first create a [GitHub issue](https://github.com/craftcms/cms/issues) for it if there’s not one already. As much as we appreciate community contributions, we are pretty selective about which sorts of features should make it into Craft itself rather than a plugin, so don’t take it the wrong way if we advise you to pursue the idea as a plugin instead.

## Pull Requests

Pull requests should clearly describe the problem and solution. Include the relevant issue number if there is one. If the pull request fixes a bug, it should include a new test case that demonstrates the issue, if possible.

<br>
Thanks for being awesome.

:cocktail:

