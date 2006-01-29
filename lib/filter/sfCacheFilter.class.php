<?php

//$response->setHttpHeader('Last-Modified', $response->getDate(time()));


/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage filter
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfCacheFilter extends sfFilter
{
  private
    $cacheManager = null,
    $request      = null,
    $response     = null,
    $toSave       = array();

  public function initialize($context, $parameters = array())
  {
    parent::initialize($context, $parameters);

    $this->cacheManager = $context->getViewCacheManager();
    $this->request      = $context->getRequest();
    $this->response     = $context->getResponse();
  }

  /**
   * Execute this filter.
   *
   * @param FilterChain A FilterChain instance.
   *
   * @return void
   */
  public function execute ($filterChain)
  {
    if (sfConfig::get('sf_cache'))
    {
      // no cache if GET or POST parameters
      if (count($_GET) || count($_POST))
      {
        $filterChain->execute();

        return;
      }

      $context = $this->getContext();

      // register our cache configuration
      $cacheConfigFile = $context->getModuleName().'/'.sfConfig::get('sf_app_module_config_dir_name').'/cache.yml';
      if (is_readable(sfConfig::get('sf_app_module_dir').'/'.$cacheConfigFile))
      {
        require(sfConfigCache::getInstance()->checkConfig(sfConfig::get('sf_app_module_dir_name').'/'.$cacheConfigFile, array('moduleName' => $context->getModuleName())));
      }

      // page cache
      list($uri, $suffix) = $this->cacheManager->getInternalUri('page');
      $this->toSave[$uri.'_'.$suffix] = false;
      if ($this->cacheManager->hasCacheConfig($uri, $suffix))
      {
        $inCache = $this->getPageCache($uri, $suffix);
        $this->toSave[$uri.'_'.$suffix] = !$inCache;

        if ($inCache)
        {
          // page is in cache, so no need to run execution filter
          $filterChain->executionFilterDone();
        }
      }
      else
      {
        list($uri, $suffix) = $this->cacheManager->getInternalUri('slot');
        $this->toSave[$uri.'_'.$suffix] = false;
        if ($this->cacheManager->hasCacheConfig($uri, $suffix))
        {
          $inCache = $this->getActionCache($uri, $suffix);
          $this->toSave[$uri.'_'.$suffix] = !$inCache;
        }
      }
    }

    // execute next filter
    $filterChain->execute();
  }

  /**
   * Execute this filter.
   *
   * @param FilterChain A FilterChain instance.
   *
   * @return void
   */
  public function executeBeforeRendering ($filterChain)
  {
    if (sfConfig::get('sf_cache'))
    {
      // no cache if GET or POST parameters
      if (count($_GET) || count($_POST))
      {
        $filterChain->execute();

        return;
      }

      // cache only 200 HTTP status
      if ($this->response->getStatusCode() == 200)
      {
        // save page in cache
        list($uri, $suffix) = $this->cacheManager->getInternalUri('page');
        if ($this->toSave[$uri.'_'.$suffix])
        {
          $this->setPageCache($uri, $suffix);
        }

        // save slot in cache
        list($uri, $suffix) = $this->cacheManager->getInternalUri('slot');
        if (isset($this->toSave[$uri.'_'.$suffix]) && $this->toSave[$uri.'_'.$suffix])
        {
          $this->setActionCache($uri, $suffix);
        }
      }
    }

    // execute next filter
    $filterChain->execute();
  }

  private function setPageCache($uri, $suffix)
  {
    $context = $this->getContext();

    if ($context->getController()->getRenderMode() != sfView::RENDER_CLIENT)
    {
      return;
    }

    // save content in cache
    $content = $this->cacheManager->set($this->response->getContent(), $uri, $suffix);

    $this->response->setContent($content);

    if (sfConfig::get('sf_logging_active'))
    {
      $context->getLogger()->info('{sfCacheFilter} save page "'.$uri.' - '.$suffix.'" in cache');
    }
  }

  private function getPageCache($uri, $suffix)
  {
    $context = $this->getContext();

    // ignore cache?
    if (sfConfig::get('sf_debug') && $this->request->getParameter('ignore_cache', false, 'symfony/request/sfWebRequest') == true)
    {
      if (sfConfig::get('sf_logging_active'))
      {
        $context->getLogger()->info('{sfCacheFilter} discard page cache for "'.$uri.' - '.$suffix.'"');
      }

      return false;
    }

    // get the current action information
    $moduleName = $context->getModuleName();
    $actionName = $context->getActionName();

    $retval = $this->cacheManager->get($uri, $suffix);

    if (sfConfig::get('sf_logging_active'))
    {
      $context->getLogger()->info('{sfCacheFilter} page cache "'.$uri.' - '.$suffix.'" '.($retval ? 'exists' : 'does not exist'));
    }

    if ($retval !== null)
    {
      $controller = $context->getController();
      if ($controller->getRenderMode() == sfView::RENDER_VAR)
      {
        $controller->getActionStack()->getLastEntry()->setPresentation($retval);
        $this->response->setContent('');
      }
      else
      {
        $this->response->setContent($retval);
      }

      return true;
    }

    return false;
  }

  private function setActionCache($uri, $suffix)
  {
    $content = $this->response->getParameter($uri.'_'.$suffix, null, 'symfony/cache');
    $this->cacheManager->set($content, $uri, $suffix);

    if (sfConfig::get('sf_logging_active'))
    {
      $this->getContext()->getLogger()->info('{sfCacheFilter} save slot "'.$uri.' - '.$suffix.'" in cache');
    }
  }

  private function getActionCache($uri, $suffix)
  {
    // ignore cache parameter? (only available in debug mode)
    if (sfConfig::get('sf_debug') && $this->request->getParameter('ignore_cache', false, 'symfony/request/sfWebRequest') == true)
    {
      if (sfConfig::get('sf_logging_active'))
      {
        $this->getContext()->getLogger()->info('{sfCacheFilter} discard cache for "'.$uri.'" / '.$suffix.'');
      }
    }
    else
    {
      // retrieve content from cache
      $retval = $this->cacheManager->get($uri, $suffix);

      if ($retval)
      {
        $this->response->setParameter($uri.'_'.$suffix, $retval, 'symfony/cache');
      }

      if (sfConfig::get('sf_logging_active'))
      {
        $this->getContext()->getLogger()->info('{sfCacheFilter} cache for "'.$uri.' - '.$suffix.'" '.($retval !== null ? 'exists' : 'does not exist'));
      }

      return ($retval ? true : false);
    }

    return false;
  }
}

?>