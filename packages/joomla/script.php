<?php

\defined('_JEXEC') or exit;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Enables the Content - Flexa plugin on first install, so {flexa} tags work right away.
 */
class Pkg_FlexaInstallerScript
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
            ->where($db->quoteName('folder').' = '.$db->quote('content'))
            ->where($db->quoteName('element').' = '.$db->quote('flexa'));

        $db->setQuery($query)->execute();
    }
}
