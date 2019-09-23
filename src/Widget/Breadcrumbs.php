<?php

namespace Yiisoft\Widget;

use Yiisoft\Html\Html;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\View\WebView;

/**
 * Breadcrumbs displays a list of links indicating the position of the current page in the whole site hierarchy.
 *
 * For example, breadcrumbs like "Home / Sample Post / Edit" means the user is viewing an edit page for the
 * "Sample Post". He can click on "Sample Post" to view that page, or he can click on "Home" to return to the homepage.
 *
 * To use Breadcrumbs, you need to configure its {@see links} property, which specifies the links to be displayed. For
 * example,
 *
 * ```php
 * // $this is the view object currently being used
 * echo Breadcrumbs::widget()
 *     -> itemTemplate() => "<li><i>{link}</i></li>\n", // template for all links
 *     -> links() => [
 *         [
 *             'label' => 'Post Category',
 *             'url' => 'post-category/view?id=10',
 *             'template' => "<li><b>{link}</b></li>\n", // template for this link only
 *         ],
 *         ['label' => 'Sample Post', 'url' => 'post/edit?id=1',
 *         'Edit',
 *     ];
 * ```
 *
 * Because breadcrumbs usually appears in nearly every page of a website, you may consider placing it in a layout view.
 * You can use a view parameter (e.g. `$this->params['breadcrumbs']`) to configure the links in different views. In the
 * layout view, you assign this view parameter to the {@see links} property like the following:
 *
 * ```php
 * // $this is the view object currently being used
 * echo Breadcrumbs::widget()
 *     ->links() => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [];
 * ```
 *
 * @method static Breadcrumbs widget(WebView $view)
 */
class Breadcrumbs extends Widget
{
    /**
     * @var string the name of the breadcrumb container tag.
     */
    private $tag = 'ul';

    /**
     * @var array the HTML attributes for the breadcrumb container tag.
     *
     * {@see \Yiisoft\Html\Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    private $options = ['class' => 'breadcrumb'];

    /**
     * @var bool whether to HTML-encode the link labels.
     */
    private $encodeLabels = true;

    /**
     * @var bool the first hyperlink in the breadcrumbs (called home link). If this property is true, it will default
     *           to a link pointing to HomeUrl '\' with the label 'Home'. If this property is false, the home link will
     *           not be rendered.
     */
    private $homeLink = true;

    /**
     * @var array $homeUrlLink
     */
    private $homeUrlLink;

    /**
     * @var array list of links to appear in the breadcrumbs. If this property is empty, the widget will not render
     *            anything. Each array element represents a single link in the breadcrumbs with the following structure:
     *
     * ```php
     * [
     *     'label' => 'label of the link',  // required
     *     'url' => 'url of the link',      // optional, will be processed by Url::to()
     *     'template' => 'own template of the item', // optional, if not set $this->itemTemplate will be used
     * ]
     * ```
     *
     * If a link is active, you only need to specify its "label", and instead of writing `['label' => $label]`, you may
     * simply use `$label`.
     *
     * Additional array elements for each link will be treated as the HTML attributes for the hyperlink tag.
     * For example, the following link specification will generate a hyperlink with CSS class `external`:
     *
     * ```php
     * [
     *     'label' => 'demo',
     *     'url' => 'http://example.com',
     *     'class' => 'external',
     * ]
     * ```
     *
     * Each individual link can override global {@see encodeLabels} param like the following:
     *
     * ```php
     * [
     *     'label' => '<strong>Hello!</strong>',
     *     'encode' => false,
     * ]
     * ```
     */
    private $links = [];

    /**
     * @var string the template used to render each inactive item in the breadcrumbs. The token `{link}` will be
     *             replaced with the actual HTML link for each inactive item.
     */
    private $itemTemplate = "<li>{link}</li>\n";

    /**
     * @var string the template used to render each active item in the breadcrumbs. The token `{link}` will be replaced
     *             with the actual HTML link for each active item.
     */
    private $activeItemTemplate = "<li class=\"active\">{link}</li>\n";

    /**
     * Renders the widget.
     *
     * @return string the result of widget execution to be outputted.
     */
    public function getContent(): string
    {
        if (empty($this->links)) {
            return '';
        }

        $links = [];

        if ($this->homeLink === true) {
            $links[] = $this->renderItem([
                'label' => 'Home',
                'url'   => '/',
            ], $this->itemTemplate);
        } elseif (!empty($this->homeUrlLink)) {
            $links[] = $this->renderItem($this->homeUrlLink, $this->itemTemplate);
        }

        foreach ($this->links as $link) {
            if (!is_array($link)) {
                $link = ['label' => $link];
            }

            if (!empty($link)) {
                $links[] = $this->renderItem($link, isset($link['url']) ? $this->itemTemplate : $this->activeItemTemplate);
            }
        }

        return Html::tag(!empty($this->tag) ? $this->tag : false, implode('', $links), $this->options);
    }

    /**
     * Renders a single breadcrumb item.
     *
     * @param array  $link the link to be rendered. It must contain the "label" element. The "url" element is optional.
     * @param string $template the template to be used to rendered the link. The token "{link}" will be replaced by the
     *                         link.
     *
     * @throws \InvalidArgumentException if `$link` does not have "label" element.
     *
     * @return string the rendering result
     */
    protected function renderItem(array $link, string $template): string
    {
        $encodeLabel = ArrayHelper::remove($link, 'encode', $this->encodeLabels);
        $label = $encodeLabel ? Html::encode($link['label']) : $link['label'];

        if (isset($link['template'])) {
            $template = $link['template'];
        }

        if (isset($link['url'])) {
            $options = $link;
            unset($options['template'], $options['label'], $options['url']);
            $link = Html::a($label, $link['url'], $options);
        } else {
            $link = $label;
        }

        return strtr($template, ['{link}' => $link]);
    }

    /**
     * {@see tag}
     *
     * @param string $value
     *
     * @return $this
     */
    public function tag(string $value): self
    {
        $this->tag = $value;

        return $this;
    }

    /**
     * {@see options}
     *
     * @param array $value
     *
     * @return $this
     */
    public function options(array $value): self
    {
        $this->options = $value;

        return $this;
    }

    /**
     * {@see encodeLabel}
     *
     * @param boolean $value
     *
     * @return $this
     */
    public function encodeLabels(bool $value): self
    {
        $this->encodeLabels = $value;

        return $this;
    }

    /**
     * {@see homeLink}
     *
     * @param bool $value
     *
     * @return $this
     */
    public function homeLink(bool $value): self
    {
        $this->homeLink = $value;

        return $this;
    }

    /**
     * {@see links}
     *
     * @param array $value
     *
     * @return $this
     */
    public function homeUrlLink(array $value): self
    {
        $this->homeUrlLink = $value;

        return $this;
    }

    /**
     * {@see links}
     *
     * @param array $value
     *
     * @return $this
     */
    public function links(array $value): self
    {
        if (!array_key_exists('label', $value)) {
            throw new \InvalidArgumentException('The "label" element is required for each link.');
        }

        $this->links = $value;

        return $this;
    }

    /**
     * {@see itemTemplate}
     *
     * @param string $value
     *
     * @return $this
     */
    public function itemTemplate(string $value): self
    {
        $this->itemTemplate = $value;

        return $this;
    }

    /**
     * {@see activeItemTemplate}
     *
     * @param string $value
     *
     * @return $this
     */
    public function activeItemTemplate(string $value): self
    {
        $this->activeItemTemplate = $value;

        return $this;
    }

    public function __toString()
    {
        return $this->run();
    }
}
