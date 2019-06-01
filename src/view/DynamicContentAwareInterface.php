<?php

namespace Yiisoft\View\View;

/**
 * DynamicContentAwareInterface is the interface that should be implemented by classes
 * which support a [[View]] dynamic content feature.
 */
interface DynamicContentAwareInterface
{
    /**
     * Returns a list of placeholders for dynamic content. This method
     * is used internally to implement the content caching feature.
     *
     * @return array a list of placeholders.
     */
    public function getDynamicPlaceholders(): array;

    /**
     * Sets a list of placeholders for dynamic content. This method
     * is used internally to implement the content caching feature.
     *
     * @param array $placeholders a list of placeholders.
     */
    public function setDynamicPlaceholders(array $placeholders): void;

    /**
     * Adds a placeholder for dynamic content.
     * This method is used internally to implement the content caching feature.
     *
     * @param string $name       the placeholder name.
     * @param string $statements the PHP statements for generating the dynamic content.
     */
    public function addDynamicPlaceholder(string $name, string $statements): void;
}
