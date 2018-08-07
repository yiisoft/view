<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\view;

/**
 * DynamicContentAwareInterface is the interface that should be implemented by classes
 * which support a [[View]] dynamic content feature.
 *
 * @author Sergey Makinen <sergey@makinen.ru>
 */
interface DynamicContentAwareInterface
{
    /**
     * Returns a list of placeholders for dynamic content. This method
     * is used internally to implement the content caching feature.
     * @return array a list of placeholders.
     */
    public function getDynamicPlaceholders();

    /**
     * Sets a list of placeholders for dynamic content. This method
     * is used internally to implement the content caching feature.
     * @param array $placeholders a list of placeholders.
     */
    public function setDynamicPlaceholders($placeholders);

    /**
     * Adds a placeholder for dynamic content.
     * This method is used internally to implement the content caching feature.
     * @param string $name the placeholder name.
     * @param string $statements the PHP statements for generating the dynamic content.
     */
    public function addDynamicPlaceholder($name, $statements);
}
