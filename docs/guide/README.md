Assets
======

An asset in Yii is a file that can be referenced on a Web page. It can be a CSS file, a JavaScript file, an image or video file, etc. These files are usually placed in non-public directory that is then becomes accessible from public directory either by copying or by symlinking. This preparation step is called publishing.

It is often preferable to manage assets programmatically. For example, when you use the  [Yiisoft\Boostrap4\BoostrapAsset] widget in a page, it will automatically include the required CSS and JavaScript files, instead of asking you to manually find these files and include them. And when you upgrade the widget to a new version, it will automatically use the new version of the asset files. In this tutorial, we will describe the powerful asset management capability provided in Yii.

- [Config composer.json](config-composer-json.md)
- [Config AssetManager](config-assetmanager.md)
- [AssetBundles](assetbundles.md)


