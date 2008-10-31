<?php

/**
 * Model generator configuration.
 *
 * @package    symfony
 * @subpackage generator
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfModelGeneratorConfiguration
{
  protected
    $configuration = array();

  /**
   * Constructor.
   */
  public function __construct()
  {
    $this->compile();
  }

  protected function compile()
  {
    $config = array(
      'default' => $this->getFieldsDefault(),
      'list'    => $this->getFieldsList(),
      'filter'  => $this->getFieldsFilter(),
      'form'    => $this->getFieldsForm(),
      'new'     => $this->getFieldsNew(),
      'edit'    => $this->getFieldsEdit(),
    );

    // inheritance rules:
    // new|edit < form < default
    // list < default
    // filter < default
    $this->configuration = array(
      'list'   => array(
        'fields'         => array(),
        'layout'         => $this->getListLayout(),
        'title'          => $this->getListTitle(),
        'actions'        => $this->getListActions(),
        'object_actions' => $this->getListObjectActions(),
        'batch_actions'  => $this->getListBatchActions(),
      ),
      'filter' => array(
        'fields'  => array(),
      ),
      'form'   => array(
        'fields'  => array(),
      ),
      'new'    => array(
        'fields'  => array(),
        'title'   => $this->getNewTitle(),
        'actions' => $this->getNewActions() ? $this->getNewActions() : $this->getFormActions(),
      ),
      'edit'   => array(
        'fields'  => array(),
        'title'   => $this->getEditTitle(),
        'actions' => $this->getEditActions() ? $this->getEditActions() : $this->getFormActions(),
      ),
    );

    foreach (array_keys($config['default']) as $field)
    {
      $formConfig = array_merge($config['default'][$field], $config['form'][$field]);

      $this->configuration['list']['fields'][$field]   = new sfModelGeneratorConfigurationField($field, array_merge($config['default'][$field], $config['list'][$field]));
      $this->configuration['filter']['fields'][$field] = new sfModelGeneratorConfigurationField($field, array_merge($config['default'][$field], $config['filter'][$field]));
      $this->configuration['form']['fields'][$field]   = new sfModelGeneratorConfigurationField($field, $formConfig);
      $this->configuration['new']['fields'][$field]    = new sfModelGeneratorConfigurationField($field, array_merge($formConfig, $config['new'][$field]));
      $this->configuration['edit']['fields'][$field]   = new sfModelGeneratorConfigurationField($field, array_merge($formConfig, $config['edit'][$field]));
    }

    // "virtual" fields for list
    foreach ($this->getListDisplay() as $field)
    {
      list($field, $flag) = sfModelGeneratorConfigurationField::splitFieldWithFlag($field);

      if (isset($this->configuration['list']['fields'][$field]))
      {
        continue;
      }

      $this->configuration['list']['fields'][$field] = new sfModelGeneratorConfigurationField($field, array_merge(
        isset($config['default'][$field]) ? $config['default'][$field] : array(),
        isset($config['list'][$field]) ? $config['list'][$field] : array(),
        array('is_real' => false, 'type' => 'Text', 'flag' => $flag)
      ));
    }

    // "virtual" fields for filters
    foreach ($this->getFilterDisplay() as $field)
    {
      list($field, $flag) = sfModelGeneratorConfigurationField::splitFieldWithFlag($field);

      if (isset($this->configuration['filter']['fields'][$field]))
      {
        continue;
      }

      if (!in_array($flag, array('_', '~')))
      {
        throw new InvalidArgumentException(sprintf('The virtual filter "%s" field can only be a partial or a component.', $field));
      }

      $this->configuration['filter']['fields'][$field] = new sfModelGeneratorConfigurationField($field, array_merge(
        isset($config['default'][$field]) ? $config['default'][$field] : array(),
        isset($config['filter'][$field]) ? $config['filter'][$field] : array(),
        array('is_real' => false, 'type' => 'Text', 'flag' => $flag)
      ));
    }

    // "virtual" fields for form/edit/new
    foreach (array('new', 'edit') as $context)
    {
      $method = sprintf('get%sDisplay', $context);
      if (!$fieldsets = $this->$method())
      {
        $fieldsets = $this->getFormDisplay();
      }
      foreach ($fieldsets as $fieldset => $fields)
      {
        foreach ($fields as $field)
        {
          list($field, $flag) = sfModelGeneratorConfigurationField::splitFieldWithFlag($field);

          if (isset($this->configuration[$context]['fields'][$field]))
          {
            continue;
          }

          if (!in_array($flag, array('_', '~')))
          {
            throw new InvalidArgumentException(sprintf('The virtual %s "%s" field can only be a partial or a component.', $context, $field));
          }

          $this->configuration[$context]['fields'][$field] = new sfModelGeneratorConfigurationField($field, array_merge(
            isset($config['default'][$field]) ? $config['default'][$field] : array(),
            isset($config[$context][$field]) ? $config[$context][$field] : array(),
            array('is_real' => false, 'type' => 'Text', 'flag' => $flag)
          ));
        }
      }
    }

    // form actions
    foreach (array('edit', 'new') as $context)
    {
      foreach ($this->configuration[$context]['actions'] as $action => &$parameters)
      {
        $this->fixActionParameters($action, $parameters);
      }
    }

    // list actions
    foreach ($this->configuration['list']['actions'] as $action => &$parameters)
    {
      $this->fixActionParameters($action, $parameters);
    }

    // list object actions
    foreach ($this->configuration['list']['object_actions'] as $action => &$parameters)
    {
      $this->fixActionParameters($action, $parameters);
    }

    // list batch actions
    foreach ($this->configuration['list']['batch_actions'] as $action => &$parameters)
    {
      $this->fixActionParameters($action, $parameters);
    }

    // list field configuration
    $this->configuration['list']['display'] = array();
    foreach ($this->getListDisplay() as $name)
    {
      list($name, $flag) = sfModelGeneratorConfigurationField::splitFieldWithFlag($name);
      if (!isset($this->configuration['list']['fields'][$name]))
      {
        throw new InvalidArgumentException(sprintf('The field "%s" does not exist.', $name));
      }
      $field = $this->configuration['list']['fields'][$name];
      $field->setFlag($flag);
      $this->configuration['list']['display'][$name] = $field;
    }

    // list params configuration
    $this->configuration['list']['params'] = $this->getListParams();
    preg_match_all('/%%([^%]+)%%/', $this->getListParams(), $matches, PREG_PATTERN_ORDER);
    foreach ($matches[1] as $name)
    {
      list($name, $flag) = sfModelGeneratorConfigurationField::splitFieldWithFlag($name);
      if (!isset($this->configuration['list']['fields'][$name]))
      {
        throw new InvalidArgumentException(sprintf('The field "%s" does not exist.', $name));
      }
      $field = $this->configuration['list']['fields'][$name];
      $field->setFlag($flag);
      $this->configuration['list']['params'] = str_replace('%%'.$flag.$name.'%%', '%%'.$name.'%%', $this->configuration['list']['params']);
    }

    // filter field configuration
    $this->configuration['filter']['display'] = array();
    foreach ($this->getFilterDisplay() as $name)
    {
      list($name, $flag) = sfModelGeneratorConfigurationField::splitFieldWithFlag($name);
      if (!isset($this->configuration['filter']['fields'][$name]))
      {
        throw new InvalidArgumentException(sprintf('The field "%s" does not exist.', $name));
      }
      $field = $this->configuration['filter']['fields'][$name];
      $field->setFlag($flag);
      $this->configuration['filter']['display'][$name] = $field;
    }

    // form field configuration
    foreach (array('edit', 'new') as $context)
    {
      $this->configuration[$context]['display'] = array();
      $method = sprintf('get%sDisplay', ucfirst($context));
      if (!$fieldsets = $this->$method())
      {
        $fieldsets = $this->getFormDisplay();
      }
      foreach ($fieldsets as $fieldset => $names)
      {
        $this->configuration[$context]['display'][$fieldset] = array();

        foreach ($names as $name)
        {
          list($name, $flag) = sfModelGeneratorConfigurationField::splitFieldWithFlag($name);
          if (!isset($this->configuration[$context]['fields'][$name]))
          {
            throw new InvalidArgumentException(sprintf('The field "%s" does not exist.', $name));
          }
          $field = $this->configuration[$context]['fields'][$name];
          $field->setFlag($flag);
          $this->configuration[$context]['display'][$fieldset][$name] = $field;
        }
      }
    }

    // action credentials
    $this->configuration['credentials'] = array(
      'list'   => array(),
      'new'    => array(),
      'create' => array(),
      'edit'   => array(),
      'update' => array(),
      'delete' => array(),
    );
    foreach ($this->getActionsDefault() as $action => $params)
    {
      if (0 === strpos($action, '_'))
      {
        $action = substr($action, 1);
      }

      $this->configuration['credentials'][$action] = isset($params['credentials']) ? $params['credentials'] : array();
    }
    $this->configuration['credentials']['create'] = $this->configuration['credentials']['new'];
    $this->configuration['credentials']['update'] = $this->configuration['credentials']['edit'];
  }

  public function getContextConfiguration($context, $fields = null)
  {
    if (!isset($this->configuration[$context]))
    {
      throw new InvalidArgumentException(sprintf('The context "%s" does not exist.', $context));
    }

    if (is_null($fields))
    {
      return $this->configuration[$context];
    }

    $f = array();
    foreach ($fields as $field)
    {
      $f[$field] = $this->configuration[$context]['fields'][$field];
    }

    return $f;
  }

  public function getFieldConfiguration($context, $field)
  {
    if (!isset($this->configuration[$context]))
    {
      throw new InvalidArgumentException(sprintf('The context "%s" does not exist.', $context));
    }

    if (!isset($this->configuration[$context]['fields'][$field]))
    {
      throw new InvalidArgumentException(sprintf('Field "%s" does not exist.', $field));
    }

    return $this->configuration[$context]['fields'][$field];
  }

  /**
   * Gets the configuration for a given field.
   *
   * @param  string  $key     The configuration key (title.list.name for example)
   * @param  mixed   $default The default value if none has been defined
   * @param  Boolean $escaped Whether to escape single quote (false by default)
   *
   * @return mixed           The configuration value
   */
  public function getValue($key, $default = null, $escaped = false)
  {
    if (preg_match('/^(?P<context>[^\.]+)\.fields\.(?P<field>[^\.]+)\.(?P<key>.+)$/', $key, $matches))
    {
      $v = $this->getFieldConfiguration($matches['context'], $matches['field'])->getConfig($matches['key'], $default);
    }
    else if (preg_match('/^(?P<context>[^\.]+)\.(?P<key>.+)$/', $key, $matches))
    {
      $v = sfModelGeneratorConfiguration::getFieldConfigValue($this->getContextConfiguration($matches['context']), $matches['key'], $default);
    }
    else
    {
      throw new InvalidArgumentException(sprintf('Configuration key "%s" is invalid.', $key));
    }

    return $escaped ? str_replace("'", "\\'", $v) : $v;
  }

  /**
   * Gets the value for a given key.
   *
   * @param array  $config  The configuration
   * @param string $key     The key name
   * @param mixed  $default The default value
   *
   * @return mixed The key value
   */
  static public function getFieldConfigValue($config, $key, $default = null)
  {
    $ref   =& $config;
    $parts =  explode('.', $key);
    $count =  count($parts);
    for ($i = 0; $i < $count; $i++)
    {
      $partKey = $parts[$i];
      if (!isset($ref[$partKey]))
      {
        return $default;
      }

      if ($count == $i + 1)
      {
        return $ref[$partKey];
      }
      else
      {
        $ref =& $ref[$partKey];
      }
    }

    return $default;
  }

  protected function fixActionParameters($action, &$parameters)
  {
    if (is_null($parameters))
    {
      $parameters = array();
    }

    if (!isset($parameters['params']))
    {
      $parameters['params'] = array();
    }

    if ('_delete' == $action && !isset($parameters['confirm']))
    {
      $parameters['confirm'] = 'Are you sure?';
    }

    $parameters['class_suffix'] = strtolower('_' == $action[0] ? substr($action, 1) : $action);

    // merge with defaults
    $defaults = $this->getActionsDefault();
    if (isset($defaults[$action]))
    {
      $parameters = array_merge($defaults[$action], $parameters);
    }

    if (isset($parameters['label']))
    {
      $label = $parameters['label'];
    }
    else if ('_' != $action[0])
    {
      $label = $action;
    }
    else
    {
      $label = '_list' == $action ? 'Cancel' : substr($action, 1);
    }

    $parameters['label'] = sfInflector::humanize($label);
  }
}