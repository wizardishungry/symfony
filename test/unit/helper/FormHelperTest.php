<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__FILE__).'/../../bootstrap/unit.php';
require_once $_test_dir.'/unit/sfContextMock.class.php';

class myController
{
  public function genUrl($parameters = array(), $absolute = false)
  {
    return 'module/action';
  }
}

$t = new lime_test(5, new lime_output_color());

$context = sfContext::getInstance(array(
  'controller' => 'myController',
));

require_once(dirname(__FILE__).'/../../../lib/helper/UrlHelper.php');
require_once(dirname(__FILE__).'/../../../lib/helper/TagHelper.php');
require_once(dirname(__FILE__).'/../../../lib/helper/FormHelper.php');

// form_tag()
$t->diag('form_tag()');
$t->is(form_tag(), '<form method="post" action="module/action">', 'form_tag() creates a form tag');

// options
$t->is(form_tag('', array('class' => 'foo')), '<form class="foo" method="post" action="module/action">', 'form_tag() takes an array of attribute options');
$t->is(form_tag('', array('method' => 'get')), '<form method="get" action="module/action">', 'form_tag() takes a "method" as an option');
$t->is(form_tag('', array('multipart' => true)), '<form method="post" enctype="multipart/form-data" action="module/action">', 'form_tag() takes a "multipart" boolean option');
$t->is(form_tag('', array('method' => 'put')), '<form method="post" action="module/action"><input type="hidden" name="sf_method" value="put" />', 'form_tag() creates a hidden field for methods different from GET or POST');
