<?php

namespace Yiisoft\Widget;

use Yiisoft\Data\Reader\Sort;
use Yiisoft\Factory\Exceptions\InvalidConfigException;
use Yiisoft\Html\Html;

/**
 * LinkSorter renders a list of sort links for the given sort definition.
 * LinkSorter will generate a hyperlink for every attribute declared in [[sort]].
 * For more details and usage information on LinkSorter, see the [guide article on sorting](guide:output-sorting).
 * @method static LinkSorter widget()
 */
class LinkSorter extends Widget
{
    /**
     * @var \Yiisoft\Data\Reader\Sort the sort definition
     */
    public $sort;
    /**
     * @var array list of the attributes that support sorting. If not set, it will be determined
     *            using [[Sort::attributes]].
     */
    public $attributes;
    /**
     * @var array HTML attributes for the sorter container tag.
     *
     * @see Html::ul() for special attributes.
     * @see Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = ['class' => 'sorter'];
    /**
     * @var array HTML attributes for the link in a sorter container tag which are passed to [[Sort::link()]].
     *
     * @see Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $linkOptions = [];

    /**
     * Initializes the sorter.
     */
    public function init(): void
    {
        parent::init();

        if ($this->sort === null) {
            throw new InvalidConfigException('The "sort" property must be set.');
        }
    }

    public function sort(Sort $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    public function attributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Executes the widget.
     * This method renders the sort links.
     *
     * @return string the result of widget execution to be outputted.
     */
    public function run(): string
    {
        return $this->renderSortLinks();
    }

    /**
     * Renders the sort links.
     *
     * @return string the rendering result
     */
    protected function renderSortLinks()
    {
        $attributes = empty($this->attributes) ? array_keys($this->sort->attributes) : $this->attributes;
        $links = [];
        foreach ($attributes as $name) {
            $links[] = $this->sort->link($name, $this->linkOptions);
        }

        return Html::ul($links, array_merge($this->options, ['encode' => false]));
    }
}
