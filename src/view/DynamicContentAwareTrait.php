<?php
namespace Yiisoft\View\View;

/**
 * DynamicContentAwareTrait implements common methods for classes
 * which support a [[View]] dynamic content feature.
 */
trait DynamicContentAwareTrait
{
    /**
     * @var string[] a list of placeholders for dynamic content
     */
    private $_dynamicPlaceholders;

    /**
     * Returns the view object that can be used to render views or view files using dynamic contents.
     *
     * @return Template the view object that can be used to render views or view files.
     */
    abstract protected function getView(): Template;

    /**
     * {@inheritdoc}
     */
    public function getDynamicPlaceholders(): array
    {
        return $this->_dynamicPlaceholders;
    }

    /**
     * {@inheritdoc}
     */
    public function setDynamicPlaceholders(array $placeholders): void
    {
        $this->_dynamicPlaceholders = $placeholders;
    }

    /**
     * {@inheritdoc}
     */
    public function addDynamicPlaceholder(string $name, string $statements): void
    {
        $this->_dynamicPlaceholders[$name] = $statements;
    }

    /**
     * Replaces placeholders in $content with results of evaluated dynamic statements.
     *
     * @param string   $content             content to be parsed.
     * @param string[] $placeholders        placeholders and their values.
     * @param bool     $isRestoredFromCache whether content is going to be restored from cache.
     *
     * @return string final content.
     */
    protected function updateDynamicContent(string $content, array $placeholders, bool $isRestoredFromCache = false): string
    {
        if (empty($placeholders) || !is_array($placeholders)) {
            return $content;
        }

        if (count($this->getView()->getDynamicContents()) === 0) {
            // outermost cache: replace placeholder with dynamic content
            foreach ($placeholders as $name => $statements) {
                $placeholders[$name] = $this->getView()->evaluateDynamicContent($statements);
            }
            $content = strtr($content, $placeholders);
        }
        if ($isRestoredFromCache) {
            $view = $this->getView();
            foreach ($placeholders as $name => $statements) {
                $view->addDynamicPlaceholder($name, $statements);
            }
        }

        return $content;
    }
}
