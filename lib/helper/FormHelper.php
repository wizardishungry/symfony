<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) 2004 David Heinemeier Hansson
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * FormHelper.
 *
 * @package    symfony
 * @subpackage helper
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     David Heinemeier Hansson
 * @version    SVN: $Id$
 */

/**
 * Returns an HTML <form> tag that points to a valid action, route or URL as defined by <i>$url_for_options</i>.
 *
 * By default, the form tag is generated in POST format, but can easily be configured along with any additional
 * HTML parameters via the optional <i>$options</i> parameter. If you are using file uploads, be sure to set the 
 * <i>multipart</i> option to true.
 *
 * <b>Options:</b>
 * - multipart - When set to true, enctype is set to "multipart/form-data".
 *
 * <b>Examples:</b>
 *   <code><?php echo form_tag('@myroute'); ?></code>
 *   <code><?php echo form_tag('/module/action', array('name' => 'myformname', 'multipart' => true)); ?></code>
 *
 * @param  string $url_for_options  valid action, route or URL
 * @param  array  $options          optional HTML parameters for the <form> tag
 *
 * @return string opening HTML <form> tag with options
 */
function form_tag($url_for_options = '', $options = array())
{
  $options = _parse_attributes($options);

  $html_options = $options;

  $html_options['method'] = isset($html_options['method']) ? strtolower($html_options['method']) : 'post';

  if (_get_option($html_options, 'multipart'))
  {
    $html_options['enctype'] = 'multipart/form-data';
  }

  $html_options['action'] = url_for($url_for_options);

  $html = '';
  if (!in_array($html_options['method'], array('get', 'post')))
  {
    $html = tag('input', array('type' => 'hidden', 'name' => 'sf_method', 'value' => $html_options['method']));
    $html_options['method'] = 'post';
  }

  return tag('form', $html_options, true).$html;
}
