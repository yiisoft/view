# Yii View Change Log


## 3.0.3 under development

- no changes in this release.

## 3.0.2 October 25, 2021

- Chg #190: Update the `yiisoft/arrays` dependency to `^2.0` (vjik)

## 3.0.1 September 18, 2021

- Bug: Fix incorrect method in `web` configuration (vjik)

## 3.0.0 September 18, 2021

- Сhg: In configuration `params.php` rename parameter `commonParameters` to `parameters` (vjik)
- Chg: Remove methods `View::withAddedCommonParameters()` and `WebView::withAddedCommonParameters()` (vjik)
- Chg: In classes `View` and `WebView` rename methods `setCommonParameters()` to `setParameters()`, `setCommonParameter()` to `setParameter()`,
  `removeCommonParameter()` to `removeParameter()`, `getCommonParameter()` to `getParameter()`,
  `hasCommonParameter()` to `hasParameter()` (vjik)
- Chg: Add fluent interface for setters in `View` and `WebView` classes (vjik)
  
## 2.1.0 September 14, 2021

- New #183: Add immutable methods `View::withAddedCommonParameters()` and `WebView::withAddedCommonParameters()` (vjik)

## 2.0.1 August 30, 2021

- Chg #182: Use definitions from `yiisoft/definitions` in configuration (vjik)

## 2.0.0 August 24, 2021

- Chg: Use yiisoft/html ^2.0 (samdark)

## 1.0.1 August 20, 2021

- New #177: Add second parameter to `View::getCommonParameter()` and `WebView::getCommonParameter()` for the default
  value to be returned if the specified parameter does not exist (vjik)
- Chg #176: Finalize classes `Yiisoft\View\Event\WebView\BeforeRender`, `Yiisoft\View\Event\WebView\BodyBegin`,
  `Yiisoft\View\Event\WebView\BodyEnd`, `Yiisoft\View\Event\WebView\PageBegin`, `Yiisoft\View\Event\WebView\PageEnd`,
  `Yiisoft\View\Exception\ViewNotFoundException` (vjik)

## 1.0.0 July 05, 2021

- Initial release.
