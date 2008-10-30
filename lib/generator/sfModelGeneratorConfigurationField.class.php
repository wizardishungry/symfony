<?php

/**
 * Model generator field.
 *
 * @package    symfony
 * @subpackage generator
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfModelGeneratorConfigurationField
{
  protected
    $name   = null,
    $config = null;

  /**
   * Constructor.
   *
   * @param string $config The configuration for this field
   * @param array  $flags  The column flags
   */
  public function __construct($name, $config)
  {
    $this->name = $name;
    $this->config = $config;
  }

  public function getName()
  {
    return $this->name;
  }

  public function getConfig($key, $default = null)
  {
    if ('label' == $key && !isset($this->config['label']))
    {
      return sfInflector::humanize(sfInflector::underscore($this->name));
    }

    return sfModelGeneratorConfiguration::getFieldConfigValue($this->config, $key, $default);
  }

  public function getType()
  {
    return $this->config['type'];
  }

  /**
   * Returns true if the column maps a database column.
   *
   * @return boolean true if the column maps a database column, false otherwise
   */
  public function isReal()
  {
    return isset($this->config['is_real']) ? $this->config['is_real'] : false;
  }

  /**
   * Returns true if the column is a partial.
   *
   * @return boolean true if the column is a partial, false otherwise
   */
  public function isPartial()
  {
    return isset($this->config['is_partial']) ? $this->config['is_partial'] : false;
  }

  public function setPartial($boolean)
  {
    $this->config['is_partial'] = $boolean;
  }

  /**
   * Returns true if the column is a component.
   *
   * @return boolean true if the column is a component, false otherwise
   */
  public function isComponent()
  {
    return isset($this->config['is_component']) ? $this->config['is_component'] : false;
  }

  public function setComponent($boolean)
  {
    $this->config['is_component'] = $boolean;
  }

  /**
   * Returns true if the column has a link.
   *
   * @return boolean true if the column has a link, false otherwise
   */
  public function isLink()
  {
    return isset($this->config['is_link']) ? $this->config['is_link'] : false;
  }

  public function setLink($boolean)
  {
    $this->config['is_link'] = $boolean;
  }

  static public function splitFieldWithFlag($field)
  {
    if (in_array($flag = $field[0], array('=', '_', '~')))
    {
      $field = substr($field, 1);
    }
    else
    {
      $flag = null;
    }

    return array($field, $flag);
  }

  public function setFlag($flag)
  {
    if (is_null($flag))
    {
      return;
    }

    switch ($flag)
    {
      case '=':
        $this->setLink(true);
        break;
      case '_':
        $this->setPartial(true);
        break;
      case '~':
        $this->setComponent(true);
        break;
      default:
        throw new InvalidArgumentException(sprintf('Flag "%s" does not exist.', $flag));
    }
  }

  public function getFlag()
  {
    if ($this->isLink())
    {
      return '=';
    }
    else if ($this->isPartial())
    {
      return '_';
    }
    else if ($this->isComponent())
    {
      return '~';
    }

    return '';
  }
}
