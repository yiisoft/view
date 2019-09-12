<?php
declare(strict_types = 1);

namespace Yiisoft\Widget;

/**
 * ContentDecorator records all output between {@see begin()} and {@see end()]} calls, passes it to the given view file
 * as `$content` and then echoes rendering result.
 *
 * ```php
 * <?php ContentDecorator::begin()
 *     ->viewFile('@app/views/layouts/base.php'),
 *     ->params([]),
 *     ->view($this),
 * ]) ?>
 *
 * some content here
 *
 * <?php ContentDecorator::end() ?>
 * ```
 *
 * There are {@see \Yiisoft\View\View::beginContent()} and {@see \Yiisoft\View\View::endContent()} wrapper methods in
 * the {@see \Yiisoft\View\View} component to make syntax more friendly. In the view these could be used as follows:
 *
 * ```php
 * <?php $this->beginContent('@app/views/layouts/base.php') ?>
 *
 * some content here
 *
 * <?php $this->endContent() ?>
 * ```
 *
 * @method static ContentDecorator begin()
 * @method static ContentDecorator end()
 */
class ContentDecorator extends Widget
{
    /**
     * @var array the parameters (name => value) to be extracted and made available in the decorative view.
     */
    private $params = [];

    /**
     * @var string the view file that will be used to decorate the content enclosed by this widget. This can be
     *             specified as either the view file path or alias path.
     */
    private $viewFile;

    public function init(): void
    {
        parent::init();

        // Starts recording a clip.
        ob_start();
        ob_implicit_flush(0);
    }

    /**
     * Ends recording a clip.
     * This method stops output buffering and saves the rendering result as a named clip in the controller.
     *
     * @return string the result of widget execution to be outputted.
     */
    public function run(): string
    {
        $params = $this->params;
        $params['content'] = ob_get_clean();

        // render under the existing context

        return $this->getView()->renderFile($this->viewFile, $params);
    }

    public function params(array $value): self
    {
        $this->params = $value;

        return $this;
    }

    public function viewFile(string $value): self
    {
        $this->viewFile = $value;

        return $this;
    }
}
