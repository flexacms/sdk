<?php

namespace FlexaCms\Module\Flexa\Site\Dispatcher;

use FlexaCms\Joomla\Core\Entry;
use FlexaCms\Joomla\Core\Exceptions\FlexaException;
use FlexaCms\Joomla\Flexa;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or exit;

/**
 * Loads the entries shown by the module; markup lives in tmpl/default.php.
 */
class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData()
    {
        $data = parent::getLayoutData();
        $params = $data['params'];
        $entity = (string) $params->get('entity', '');
        $client = Flexa::client();
        $data['entries'] = [];
        $data['problem'] = null;

        if ($client === null || $entity === '') {
            $data['problem'] = Text::_('MOD_FLEXA_NOT_READY');
        } else {
            try {
                $data['entries'] = $client->entity($entity)->published()->list(1, max(1, min(50, (int) $params->get('count', 3))))->items;
            } catch (FlexaException $exception) {
                Log::add('Flexa: '.$exception->getMessage(), Log::WARNING, 'mod_flexa');
                $data['problem'] = $exception->getMessage();
            }
        }

        $itemId = (int) $params->get('link_menu', 0);
        $data['link'] = static fn (Entry $entry): ?string => $itemId > 0
            ? Route::_('index.php?option=com_flexa&view=entry&entity='.rawurlencode($entity).'&slug='.rawurlencode((string) ($entry->slug ?? $entry->id)).'&Itemid='.$itemId)
            : null;
        $data['canSeeProblems'] = $this->getApplication()->getIdentity()->authorise('core.manage', 'com_flexa');

        $assets = $this->getApplication()->getDocument()->getWebAssetManager();
        $assets->getRegistry()->addExtensionRegistryFile('com_flexa');
        $assets->useStyle('com_flexa.flexa');

        return $data;
    }
}
