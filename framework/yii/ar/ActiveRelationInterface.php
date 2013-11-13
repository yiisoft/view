<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\ar;

/**
 * ActiveRelationInterface defines the common interface to be implemented by active record relation classes.
 *
 * A class implementing this interface should also use [[ActiveRelationTrait]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
interface ActiveRelationInterface extends ActiveQueryInterface
{
	/**
	 * Specifies the relation associated with the pivot table.
	 * @param string $relationName the relation name. This refers to a relation declared in [[primaryModel]].
	 * @param callable $callable a PHP callback for customizing the relation associated with the pivot table.
	 * Its signature should be `function($query)`, where `$query` is the query to be customized.
	 * @return static the relation object itself.
	 */
	public function via($relationName, $callable = null);

	/**
	 * Finds the related records and populates them into the primary models.
	 * This method is internally used by [[ActiveQuery]]. Do not call it directly.
	 * @param string $name the relation name
	 * @param array $primaryModels primary models
	 * @return array the related models
	 */
	public function findWith($name, &$primaryModels);
}
