<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\web\JsExpression;
use yii\helpers\Json;

/**
 * EmailValidator validates that the attribute value is a valid email address.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class EmailValidator extends Validator
{
	/**
	 * @var string the regular expression used to validate the attribute value.
	 * @see http://www.regular-expressions.info/email.html
	 */
	public $pattern = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
	/**
	 * @var string the regular expression used to validate email addresses with the name part.
	 * This property is used only when [[allowName]] is true.
	 * @see allowName
	 */
	public $fullPattern = '/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/';
	/**
	 * @var boolean whether to allow name in the email address (e.g. "John Smith <john.smith@example.com>"). Defaults to false.
	 * @see fullPattern
	 */
	public $allowName = false;
	/**
	 * @var boolean whether to check whether the emails domain exists and has either an A or MX record.
	 * Be aware of the fact that this check can fail due to temporary DNS problems even if the email address is
	 * valid and an email would be deliverable. Defaults to false.
	 * @see dnsMessage
	 */
	public $checkDNS = false;
	/**
	 * @var string the error message to display when the domain of the email does not exist.
	 * It may contain the following placeholders which will be replaced accordingly by the validator:
	 *
	 * - `{attribute}`: the label of the attribute being validated
	 * - `{value}`: the value of the attribute being validated
	 *
	 * @see checkDNS
	 */
	public $dnsMessage;
	/**
	 * @var boolean whether to check port 25 for the email address.
	 * Defaults to false.
	 */
	public $checkPort = false;
	/**
	 * @var boolean whether validation process should take into account IDN (internationalized domain
	 * names). Defaults to false meaning that validation of emails containing IDN will always fail.
	 * Note that in order to use IDN validation you have to install and enable `intl` PHP extension,
	 * otherwise an exception would be thrown.
	 */
	public $enableIDN = false;


	/**
	 * Initializes the validator.
	 */
	public function init()
	{
		parent::init();
		if ($this->enableIDN && !function_exists('idn_to_ascii')) {
			throw new InvalidConfigException('In order to use IDN validation intl extension must be installed and enabled.');
		}
		if ($this->message === null) {
			$this->message = Yii::t('yii', '{attribute} is not a valid email address.');
		}
		if ($this->dnsMessage === null) {
			$this->dnsMessage = Yii::t('yii', 'The domain of this email address does not seem to exist.');
		}
	}

	/**
	 * Validates the attribute of the object.
	 * If there is any error, the error message is added to the object.
	 * @param \yii\base\Model $object the object being validated
	 * @param string $attribute the attribute being validated
	 */
	public function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;
		if (!$this->validateValue($value)) {
			$this->addError($object, $attribute, $this->message);
		}
	}

	/**
	 * Validates the given value.
	 * @param mixed $value the value to be validated.
	 * @return boolean whether the value is valid.
	 */
	public function validateValue($value)
	{
		// make sure string length is limited to avoid DOS attacks
		if (!is_string($value) || strlen($value) >= 320) {
			return false;
		}
		if (!preg_match('/^(.*<?)(.*)@(.*)(>?)$/', $value, $matches)) {
			return false;
		}
		$domain = $matches[3];
		if ($this->enableIDN) {
			$value = $matches[1] . idn_to_ascii($matches[2]) . '@' . idn_to_ascii($domain) . $matches[4];
		}
		$valid = preg_match($this->pattern, $value) || $this->allowName && preg_match($this->fullPattern, $value);
		if ($valid) {
			if ($this->checkDNS) {
				$valid = checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
			}
			if ($valid && $this->checkPort && function_exists('fsockopen')) {
				$valid = fsockopen($domain, 25) !== false;
			}
		}
		return $valid;
	}

	/**
	 * Returns the JavaScript needed for performing client-side validation.
	 * @param \yii\base\Model $object the data object being validated
	 * @param string $attribute the name of the attribute to be validated.
	 * @param \yii\web\View $view the view object that is going to be used to render views or view files
	 * containing a model form with this validator applied.
	 * @return string the client-side validation script.
	 */
	public function clientValidateAttribute($object, $attribute, $view)
	{
		$options = [
			'pattern' => new JsExpression($this->pattern),
			'fullPattern' => new JsExpression($this->fullPattern),
			'allowName' => $this->allowName,
			'message' => Html::encode(strtr($this->message, [
				'{attribute}' => $object->getAttributeLabel($attribute),
			])),
			'enableIDN' => (boolean)$this->enableIDN,
		];
		if ($this->skipOnEmpty) {
			$options['skipOnEmpty'] = 1;
		}

		ValidationAsset::register($view);
		if ($this->enableIDN) {
			PunycodeAsset::register($view);
		}
		return 'yii.validation.email(value, messages, ' . Json::encode($options) . ');';
	}
}
