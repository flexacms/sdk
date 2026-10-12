<?php

\defined('_JEXEC') or exit;

use FlexaCms\Component\Flexa\Administrator\Extension\FlexaComponent;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\FlexaCms\\Component\\Flexa'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\FlexaCms\\Component\\Flexa'));
        $container->registerServiceProvider(new RouterFactory('\\FlexaCms\\Component\\Flexa'));
        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new FlexaComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );
    }
};
