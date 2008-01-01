<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Initializes a Propel admin module.
 *
 * @package    symfony
 * @subpackage command
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfPropelInitAdminTask extends sfPropelBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('application', sfCommandArgument::REQUIRED, 'The application name'),
      new sfCommandArgument('module', sfCommandArgument::REQUIRED, 'The module name'),
      new sfCommandArgument('model', sfCommandArgument::REQUIRED, 'The model class name'),
    ));

    $this->addOptions(array(
      new sfCommandOption('theme', null, sfCommandOption::PARAMETER_REQUIRED, 'The theme name', 'default'),
    ));

    $this->aliases = array('propel-init-admin');
    $this->namespace = 'propel';
    $this->name = 'init-admin';
    $this->briefDescription = 'Initializes a Propel admin module';

    $this->detailedDescription = <<<EOF
The [propel:init-admin|INFO] task generates a Propel admin module:

  [./symfony propel:init-admin frontend article Article|INFO]

The task creates a [%module%|COMMENT] module in the [%application%|COMMENT] application
for the model class [%model%|COMMENT].

The created module is an empty one that inherit its actions and templates from
a runtime generated module in [%sf_app_cache_dir%/modules/auto%module%|COMMENT].

The generator can use a customized theme by using the [--theme|COMMENT] option:

  [./symfony propel:init-admin --theme="custom" frontend article Article|INFO]
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $properties = parse_ini_file(sfConfig::get('sf_config_dir').'/properties.ini', true);

    $constants = array(
      'PROJECT_NAME' => isset($properties['symfony']['name']) ? $properties['symfony']['name'] : 'symfony',
      'APP_NAME'     => $arguments['application'],
      'MODULE_NAME'  => $arguments['module'],
      'MODEL_CLASS'  => $arguments['model'],
      'AUTHOR_NAME'  => isset($properties['symfony']['author']) ? $properties['symfony']['author'] : 'Your name here',
      'THEME'        => $options['theme'],
    );

    $moduleDir = sfConfig::get('sf_root_dir').'/'.sfConfig::get('sf_apps_dir_name').'/'.$arguments['application'].'/'.sfConfig::get('sf_app_module_dir_name').'/'.$arguments['module'];

    // create module structure
    $finder = sfFinder::type('any')->ignore_version_control()->discard('.sf');
    $dirs = sfLoader::getGeneratorSkeletonDirs('sfPropelAdmin', $options['theme']);
    foreach ($dirs as $dir)
    {
      if (is_dir($dir))
      {
        $this->filesystem->mirror($dir, $moduleDir, $finder);
        break;
      }
    }

    // customize php and yml files
    $finder = sfFinder::type('file')->name('*.php', '*.yml');
    $this->filesystem->replaceTokens($finder->in($moduleDir), '##', '##', $constants);
  }
}