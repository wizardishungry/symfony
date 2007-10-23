<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfValidatorString validates a string. It also converts the input value to a string.
 *
 * @package    symfony
 * @subpackage validator
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfValidatorString extends sfValidator
{
  /**
   * Configures the current validator.
   *
   * Available options:
   *
   *  * max_length: The maximum length of the string
   *  * min_length: The minimum length of the string
   *
   * @see sfValidator
   */
  protected function configure($options = array(), $messages = array())
  {
    $this->setMessage('max_length', '"%value%" is too long (%max_length% characters max).');
    $this->setMessage('min_length', '"%value%" is too short (%min_length% characters min).');

    $this->setOption('empty_value', '');
  }

  /**
   * @see sfValidator
   */
  protected function doClean($value)
  {
    $clean = (string) $value;

    $length = function_exists('mb_strlen') ? mb_strlen($clean, $this->getCharset()) : strlen($value);

    if ($this->hasOption('max_length') && $length > $this->getOption('max_length'))
    {
      throw new sfValidatorError($this, 'max_length', array('value' => $value, 'max_length' => $this->getOption('max_length')));
    }

    if ($this->hasOption('min_length') && $length < $this->getOption('min_length'))
    {
      throw new sfValidatorError($this, 'min_length', array('value' => $value, 'min_length' => $this->getOption('min_length')));
    }

    return $clean;
  }

  /**
   * @see sfValidator
   */
  public function getErrorCodes()
  {
    return array_merge(parent::getErrorCodes(), array('max_length', 'min_length'));
  }
}