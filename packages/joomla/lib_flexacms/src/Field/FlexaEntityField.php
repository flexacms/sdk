<?php

namespace FlexaCms\Joomla\Field;

use FlexaCms\Joomla\Core\Exceptions\FlexaException;
use FlexaCms\Joomla\Flexa;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;

\defined('_JEXEC') or exit;

/**
 * Select list of the Flexa content models the API key can read.
 * Use with addfieldprefix="FlexaCms\Joomla\Field" and type="FlexaEntity".
 */
class FlexaEntityField extends ListField
{
    protected $type = 'FlexaEntity';

    protected function getOptions()
    {
        $options = parent::getOptions();
        $client = Flexa::client();

        if ($client === null) {
            return $options;
        }

        try {
            foreach ($client->entities() as $entity) {
                $options[] = HTMLHelper::_('select.option', (string) $entity['slug'], (string) ($entity['name'] ?? $entity['slug']));
            }
        } catch (FlexaException) {
            // The component dashboard explains connection problems.
        }

        return $options;
    }
}
