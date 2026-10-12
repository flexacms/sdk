<?php

\defined('_JEXEC') or exit;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Enables the plugin on first install, so the Flexa sources show up in the
 * YOOtheme Pro builder right away.
 */
class PlgSystemFlexayoothemeInstallerScript
{
    public function postflight(string $type, $parent): void
    {
        if ($type !== 'install') {
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled').' = 1')
            ->where($db->quoteName('type').' = '.$db->quote('plugin'))
            ->where($db->quoteName('folder').' = '.$db->quote('system'))
            ->where($db->quoteName('element').' = '.$db->quote('flexayootheme'));

        $db->setQuery($query)->execute();
    }
}
