<?php

\defined('_JEXEC') or die;

use FlexaCms\Plugin\System\FlexaYootheme\Extension\FlexaYootheme;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new FlexaYootheme((array) PluginHelper::getPlugin('system', 'flexayootheme'));
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
