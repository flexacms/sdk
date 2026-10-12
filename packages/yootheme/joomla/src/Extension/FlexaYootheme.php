<?php

namespace FlexaCms\Plugin\System\FlexaYootheme\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use YOOtheme\Application;

\defined('_JEXEC') or die;

/**
 * Loads the Flexa source module into YOOtheme Pro. Uses the connection of the
 * Flexa package (Components → Flexa).
 */
final class FlexaYootheme extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['onAfterInitialise' => 'onAfterInitialise'];
    }

    public function onAfterInitialise(): void
    {
        if (! class_exists(Application::class, false) || ! class_exists(\FlexaCms\Joomla\Flexa::class)) {
            return;
        }

        Application::getInstance()->load(JPATH_PLUGINS.'/system/flexayootheme/modules/*/bootstrap.php');
    }
}
