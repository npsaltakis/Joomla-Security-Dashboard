<?php

/**
 * @package     Joomla.Security.Dashboard
 * @subpackage  System.jsecdash
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Plugin\System\Jsecdash\Extension\Jsecdash;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(Jsecdash::class, function (Container $container) {
                $plugin = new Jsecdash(
                    (array) PluginHelper::getPlugin('system', 'jsecdash')
                );
                $plugin->setApplication(\Joomla\CMS\Factory::getApplication());
                $plugin->setDatabase($container->get(\Joomla\Database\DatabaseInterface::class));

                return $plugin;
            })
        );
    }
};
