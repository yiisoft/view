# Yii View Change Log

## 12.2.3 under development

- no changes in this release.

## 12.2.2 December 07, 2025

- Enh #287: Add PHP 8.5 support (@vjik)

## 12.2.1 July 20, 2025

- Enh #286: Allow to use `null` as value of `basePath` parameter in package configuration (@vjik)

## 12.2.0 May 16, 2025

- New #284: Allow to pass `Stringable` objects to `WebView::setTitle()` method (@vjik)
- Bug #283: Allow using multiple theme paths in `yiisoft/view → theme → pathMap` package parameter (@mariovials, @vjik)

## 12.1.0 March 15, 2025

- Chg #280: Change PHP constraint in `composer.json` to `8.1 - 8.4` (@vjik)
- Enh #282: Allow using `../` in the name of the view to refer to parent directory of the directory containing the view 
  currently being rendered (@vjik)
- Bug #280: Explicitly mark nullable parameters (@vjik)
- Bug #282: Fix exception message when relative path is used without currently rendered view (@vjik)

## 12.0.0 December 23, 2024

- New #278: Add `ViewInterface::deepClone()` method that clones object, including state cloning (@vjik)
- Chg #276: Allow to pass `null` to `ViewInterface` methods `withBasePath()` and `withContext()` (@vjik)
- Bug #279: Fix clearing theme in `View::withClearedState()` and `WebView::withClearedState()` (@vjik)

## 11.0.1 October 08, 2024

- Enh #275: Make `psr/event-dispatcher` dependency optional (@vjik)

## 11.0.0 October 02, 2024

- Chg #271: Remove deprecated methods `withDefaultExtension()` and `getDefaultExtension()` from `ViewInterface` (@vjik)
- Chg #271: Rename configuration parameter `defaultExtension` to `fallbackExtension` (@vjik)
- Chg #272: Add variadic parameter `$default` to `ViewInterface::getParameter()` (@vjik)
- Enh #269: Bump PHP version to `^8.1` and refactor code (@vjik)
- Enh #273: Use more specific psalm types in results of `WebView` methods: `getLinkTags()`, `getCss()`, `getCssFiles()`,
  `getJs()` and `getJsFiles()` (@vjik)
- Bug #273: Fix empty string and "0" keys in `WebView` methods: `registerCss()`, `registerStyleTag()`,
  `registerCssFile()`, `registerJs()`, `registerScriptTag()` and `registerJsFile()` (@vjik)

## 10.0.0 June 28, 2024

- Chg #266: Change logic of template file searching in `ViewInterface::render()` (@vjik)
- Chg #266: Remove `ViewInterface::renderFile()` (@vjik)
- Chg #266: When the view cannot be resolved in `ViewInterface::render()`, change exception from `RuntimeException` to
  `LogicException` (@vjik)

## 9.0.0 May 28, 2024

- New #242: Add `View::getLocale()` and `WebView::getLocale()` methods (@Tigrov)
- New #243: Add immutable method `ViewInterface::withTheme()` (@Gerych1984)
- Chg #232: Deprecate `ViewInterface::withDefaultExtension()` and `ViewInterface::getDefaultExtension()` in favor of 
  `withFallbackExtension()` and `getFallbackExtensions()` (@rustamwin)
- Enh #226: Adjust config to make `View` and `WebView` more configurable (@rustamwin)
- Enh #232, #233: Make fallback extension configurable & support multiple fallbacks (@rustamwin)
- Enh #248: Add types to `ViewInterface::setParameter()` and `ViewInterface::addToParameter()` parameters (@vjik)
- Enh #250: Make event dispatcher in `View` and `WebView` optional (@vjik)
- Enh #251: Make base path in `View` and `WebView` optional (@vjik)
- Bug #224: Fix signature of `CachedContent::cache()` (@vjik)
- Bug #226: Fix `reset` config for referenced definitions (@rustamwin)
- Bug #232: Fix render templates that contain dots in their name (@rustamwin)

## 8.0.0 February 16, 2023

- Chg #219: Adapt configuration group names to Yii conventions (@vjik)
- Enh #222: Add support for `yiisoft/cache` version `^3.0` (@vjik)

## 7.0.1 January 16, 2023

- Chg: Allow `yiisoft/arrays` `^3.0` (@samdark)

## 7.0.0 December 06, 2022

- Chg #211: Change return type of immutable methods in `ViewInterface` from `self` to `static` (@vjik)
- Enh #211: Raise minimum PHP version to `^8.0` (@xepozz, @vjik)
- Enh #213: Add support for `yiisoft/html` version `^3.0` (@vjik)

## 6.0.0 July 21, 2022

- New #199: Add immutable method `ViewInterface::withLocale()` that set locale (@thenotsoft, @vjik, @samdark)
- Chg #199: Renamed method `ViewInterface::setLanguage()` to `ViewInterface::setLocale()` (@thenotsoft, @samdark)
- Chg #199: Renamed method `ViewInterface::withSourceLanguage()` to
  `ViewInterface::withSourceLocale()` (@thenotsoft, @samdark)
- New #204: Add method `ViewInterface::withBasePath()` that set base path to the view directory (@thenotsoft, @vjik)
- Chg #208: Add support for `yiisoft/files` version `^2.0` (@DplusG)

## 5.0.1 June 30, 2022

- Enh #205: Add support for `yiisoft/cache` version `^2.0` (@vjik)

## 5.0.0 February 03, 2022

- New #193: Add simple view context class `ViewContext` (@vjik)
- New #193: Add method `ViewInterface::withContextPath()` that set view context path (@vjik)
- New #194: Add method `ViewInterface::addToParameter()` that add value(s) to end of specified array parameter (@vjik)
- New #195: Add method `ViewInterface::withClearedState()` that cleared state of view (parameters, blocks, etc.) (@vjik)
- Chg #195: Mutable method `ViewInterface::setPlaceholderSalt()` replaced to immutable `withPlaceholderSalt()` (@vjik)
- Chg #196: Renamed and made mutable methods of `ViewInterface`: `withTheme()` to `setTheme()`,
  `withLanguage()` to `setLanguage()` (@vjik)
- Enh #195: Methods `removeParameter()` and `removeBlock()` of `ViewInterface` returns self (@vjik)
- Enh #195: Methods of `WebView` returns self: `registerMeta()`, `registerMetaTag()`, `registerLink()`,
  `registerLinkTag()`, `registerCss()`, ` registerCssFromFile()`, `  registerStyleTag()`, ` registerCssFile()`,
  `addCssFiles()`, `addCssStrings()`, `registerJs()`, `registerScriptTag()`, `registerJsFile()`, `registerJsVar()`,
  `addJsFiles()`, `addJsStrings()`, `addJsVars()` (@vjik)
- Bug #188: Use common state for cloned instances of `View` and `WebView` (@vjik)
- Bug #195: Fix configuration: set parameters after reset `View` and `WebView` (@vjik)

## 4.0.0 October 25, 2021

- Chg #185: Add interface `ViewInterface` that classes `View` and `WebView` implement (@vjik)
- Enh #187: Improve exception message on getting not exist block or parameter in `View` and `WebView` (@vjik)
- Bug #189: Flush currently being rendered view files on change context via `View::withContext()` 
  or `WebView::withContext()` (@vjik)

## 3.0.2 October 25, 2021

- Chg #190: Update the `yiisoft/arrays` dependency to `^2.0` (@vjik)

## 3.0.1 September 18, 2021

- Bug: Fix incorrect method in `web` configuration (@vjik)

## 3.0.0 September 18, 2021

- Сhg: In configuration `params.php` rename parameter `commonParameters` to `parameters` (@vjik)
- Chg: Remove methods `View::withAddedCommonParameters()` and `WebView::withAddedCommonParameters()` (@vjik)
- Chg: In classes `View` and `WebView` rename methods `setCommonParameters()` to `setParameters()`,
  `setCommonParameter()` to `setParameter()`, `removeCommonParameter()` to `removeParameter()`, `getCommonParameter()` 
  to `getParameter()`, `hasCommonParameter()` to `hasParameter()` (@vjik)
- Chg: Add fluent interface for setters in `View` and `WebView` classes (@vjik)
  
## 2.1.0 September 14, 2021

- New #183: Add immutable methods `View::withAddedCommonParameters()` and `WebView::withAddedCommonParameters()` (@vjik)

## 2.0.1 August 30, 2021

- Chg #182: Use definitions from `yiisoft/definitions` in configuration (@vjik)

## 2.0.0 August 24, 2021

- Chg: Use `yiisoft/html` `^2.0` (@samdark)

## 1.0.1 August 20, 2021

- New #177: Add second parameter to `View::getCommonParameter()` and `WebView::getCommonParameter()` for the default
  value to be returned if the specified parameter does not exist (@vjik)
- Chg #176: Finalize classes `Yiisoft\View\Event\WebView\BeforeRender`, `Yiisoft\View\Event\WebView\BodyBegin`,
  `Yiisoft\View\Event\WebView\BodyEnd`, `Yiisoft\View\Event\WebView\PageBegin`, `Yiisoft\View\Event\WebView\PageEnd`,
  `Yiisoft\View\Exception\ViewNotFoundException` (@vjik)

## 1.0.0 July 05, 2021

- Initial release.
