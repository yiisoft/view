<?php
declare(strict_types = 1);

namespace Yiisoft\Widget;

/**
 * Block records all output between {@see begin()} and {@see end()} calls and stores it in
 * {@see \Yiisoft\View\View::$blocks}.
 *
 * {@see \Yiisoft\View\View} component contains two methods {\Yiisoft\View\View::beginBlock()} and
 * {[\Yiisoft\View\View::endBlock()}.
 *
 * The general idea is that you're defining block default in a view or layout:
 *
 * ```php
 * <?php $this->beginBlock('index') ?>
 * Nothing.
 * <?php $this->endBlock() ?>
 * ```
 *
 * And then overriding default in views:
 *
 * ```php
 * <?php $this->beginBlock('index') ?>
 * Umm... hello?
 * <?php $this->endBlock() ?>
 * ```
 *
 * in subviews show block:
 *
 * <?= $this->getBlock('index') ?>
 *
 * Second parameter defines if block content should be outputted which is desired when rendering its content but isn't
 * desired when redefining it in subviews.
 */
class Block extends Widget
{
    /**
     * @var string $id
     */
    private $id;

    /**
     * @var bool whether to render the block content in place. Defaults to false, meaning the captured block content
     *           will not be displayed.
     */
    private $renderInPlace = false;

    /**
     * Starts recording a block.
     */
    public function init(): void
    {
        parent::init();

        ob_start();
        ob_implicit_flush(0);
    }

    /**
     * Ends recording a block.
     * This method stops output buffering and saves the rendering result as a named block in the view.
     *
     * @return string the result of widget execution to be outputted.
     */
    public function run(): string
    {
        $block = ob_get_clean();

        if ($this->renderInPlace) {
            return $block;
        }

        if (!empty($block)) {
            $this->getView()->setBlocks($this->id, $block);
        }

        return '';
    }

    /**
     * {@see renderInPlace}
     *
     * @param boolean $value
     *
     * @return Widget
     */
    public function id(string $value): Widget
    {
        $this->id = $value;

        return $this;
    }

    /**
     * {@see renderInPlace}
     *
     * @param boolean $value
     *
     * @return Widget
     */
    public function renderInPlace(bool $value): Widget
    {
        $this->renderInPlace = $value;

        return $this;
    }
}
