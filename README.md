# Silverstripe CMS / Next JS integration

This module provides some base features for integrating Silverstripe CMS
with [NextJS](https://nextjs.org). Its primary intent is to work with 
the related [Silverstripe/NextJS starter kit](https://github.com/silverstripe/silverstripe-nextjs-starter).

## Features

* Configure a static build (i.e. the subset of pages to statically generate on build)

* Provides token-based content previews (full implementation provided
by the starter kit)

* **Fluent only**: remove the locale from the URL, leaving NextJS to handle
this natively

* Several custom queries and fields required for the Silverstripe/NextJS starter kit](https://github.com/silverstripe/silverstripe-nextjs-starter) internals.

### Static build configuration


Many headless frameworks will do full or partial static builds as part of deployment. This module allows you to design a strategy for what should be included in that static build.

<img src="https://raw.githubusercontent.com/silverstripe/silverstripe-nextjs/main/screeenshots/static_build.png" />


#### Limiting the static build

Each collector gets its own `Limit` field, but the aggregate build gets one as well, as you can see above. The aggregate limit will always override the individual limits of each collector.

#### Previewing the build

You can click on the "Preview" tab to show you what will be included in the build, based on the current list of collectors.

<img src="https://raw.githubusercontent.com/silverstripe/silverstripe-nextjs/main/screenshots/preview_build.png" />

#### Creating a new strategy

All you need to do is subclass the `StaticBuildCollector` class, define any fields in `$db` that could be used as parameters (`Limit` is already included), and then define a `collect()` method that need only return a `Limitable` instance.

```php
class HighlyCommentedPagesCollector extends StaticBuildCollector
{
    public function collect(): Limitable
    {
        return Page::get()
            ->filter('Comments.Count():GreaterThan', 5)
            ->limit($this->Limit);
    }
}
```

* **Recent Pages Collector**: Get the last X edited pages (e.g. statically build anything that has been worked on recently in the CMS)

* **Section Collector**: Get X pages from a given section in the site tree (e.g. statically publish everything in the `products/` section)
