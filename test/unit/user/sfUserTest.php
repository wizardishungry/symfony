<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

$t = new lime_test(39, new lime_output_color());

$_SERVER['session_id'] = 'test';
sfConfig::set('sf_test_cache_dir', sfToolkit::getTmpDir());

$dispatcher = new sfEventDispatcher();
$storage = sfStorage::newInstance('sfSessionTestStorage');
$storage->initialize(array('session_path' => sfConfig::get('sf_test_cache_dir').'/sessions'));

$user = new sfUser();
$user->initialize($dispatcher, $storage);

// ->initialize()
$t->diag('->initialize()');
$t->is($user->getCulture(), 'en', '->initialize() sets the culture to "en" by default');

$user->setCulture(null);
$user->initialize($dispatcher, $storage, array('default_culture' => 'de'));

user_flush($dispatcher, $user, $storage);

$t->is($user->getCulture(), 'de', '->initialize() sets the culture to the value of default_culture if available');

sfConfig::set('sf_i18n_default_culture', 'fr');
user_flush($dispatcher, $user, $storage);
$t->is($user->getCulture(), 'de', '->initialize() reads the culture from the session data if available');

$userBis = new sfUser();
$userBis->initialize($dispatcher, $storage);
$t->is($userBis->getCulture(), 'de', '->initialize() serializes the culture to the session data');

// ->setCulture() ->getCulture()
$t->diag('->setCulture() ->getCulture()');
$user->setCulture('fr');
$t->is($user->getCulture(), 'fr', '->setCulture() changes the current user culture');

// ->setFlash() ->getFlash() ->hasFlash()
$t->diag('->setFlash() ->getFlash() ->hasFlash()');
$user->initialize($dispatcher, $storage, array('use_flash' => true));
$user->setFlash('foo', 'bar');
$t->is($user->getFlash('foo'), 'bar', '->setFlash() sets a flash variable');
$t->is($user->hasFlash('foo'), true, '->hasFlash() returns true if the flash variable exists');
user_flush($dispatcher, $user, $storage, array('use_flash' => true));

$userBis = new sfUser();
$userBis->initialize($dispatcher, $storage, array('use_flash' => true));
$t->is($userBis->getFlash('foo'), 'bar', '->getFlash() returns a flash previously set');
$t->is($userBis->hasFlash('foo'), true, '->hasFlash() returns true if the flash variable exists');
user_flush($dispatcher, $user, $storage, array('use_flash' => true));

$userBis = new sfUser();
$userBis->initialize($dispatcher, $storage, array('use_flash' => true));
$t->is($userBis->getFlash('foo'), null, 'Flashes are automatically removed after the next request');
$t->is($userBis->hasFlash('foo'), false, '->hasFlash() returns true if the flash variable exists');

// parameter holder proxy
require_once($_test_dir.'/unit/sfParameterHolderTest.class.php');
$pht = new sfParameterHolderProxyTest($t);
$pht->launchTests($user, 'parameter');

// attribute holder proxy
require_once($_test_dir.'/unit/sfParameterHolderTest.class.php');
$pht = new sfParameterHolderProxyTest($t);
$pht->launchTests($user, 'attribute');

// new methods via sfEventDispatcher
require_once($_test_dir.'/unit/sfEventDispatcherTest.class.php');
$dispatcherTest = new sfEventDispatcherTest($t);
$dispatcherTest->launchTests($dispatcher, $user, 'user');

$storage->clear();

function user_flush($dispatcher, $user, $storage, $parameters = array())
{
  $user->shutdown();
  $user->initialize($dispatcher, $storage, $parameters);
  $parameters = $storage->getParameterHolder()->getAll();
  $storage->shutdown();
  $storage->initialize($parameters);
}
