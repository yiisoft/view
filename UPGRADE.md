# Upgrading Instructions for Yii View

This file contains the upgrade notes. These notes highlight changes that could break your
application when you upgrade the package from one version to another.

> **Important!** The following upgrading instructions are cumulative. That is, if you want
> to upgrade from version A to version C and there is version B between A and C, you need
> to following the instructions for both A and B.

## Upgrade from 10.x

- Removed `ViewInterface` methods `withDefaultExtension()` and `getDefaultExtension()`. Use `withFallbackExtension()`
  and `getFallbackExtensions()` instead, respectively. 
- Rename configuration parameter `defaultExtension` to `fallbackExtension`.
- Added variadic parameter `$default` to `ViewInterface::getParameter()`.

## Upgrade from 9.x

- Use `render()` method instead of `renderFile()` in `View` And `WebView` classes.
- Changed logic of template file searching in `ViewInterface::render()`, view name can be:
  - the absolute path to the view file, e.g. "/path/to/view.php";
  - the name of the view starting with `//` to join the base path, e.g. "//site/index";
  - the name of the view starting with `./` to join the directory containing the view currently being rendered
    (i.e., this happens when rendering a view within another view), e.g. "./widget";
  - the name of the view without the starting `//` or `./` (e.g. "site/index"), so view file will be
    looked for under the view path of the context set via `withContext()` (if the context instance was not set
    `withContext()`, it will be looked for under the base path).
