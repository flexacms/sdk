<?php

namespace FlexaCms\Component\Flexa\Site\View\Entry;

use FlexaCms\Joomla\Core\Entry;
use FlexaCms\Joomla\Core\Exceptions\FlexaException;
use FlexaCms\Joomla\Flexa;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Registry\Registry;

\defined('_JEXEC') or exit;

/**
 * One entry: reached from a list page (/blog/hello-world) or a "Flexa: single entry" menu item.
 */
class HtmlView extends BaseHtmlView
{
    public Entry $entry;

    public Registry $params;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $this->params = $app->getParams();
        $entity = $app->getInput()->getCmd('entity', (string) $this->params->get('entity', ''));
        $slug = $app->getInput()->getString('slug', (string) $this->params->get('slug', ''));
        $client = Flexa::client();

        if ($client === null || $entity === '' || $slug === '') {
            throw new \Exception(Text::_('COM_FLEXA_ENTRY_NOT_FOUND'), 404);
        }

        try {
            $entry = $client->entity($entity)->find($slug);
        } catch (FlexaException $exception) {
            Log::add('Flexa: '.$exception->getMessage(), Log::WARNING, 'com_flexa');

            throw new \Exception(Text::_('COM_FLEXA_UNAVAILABLE'), 503, $exception);
        }

        if ($entry === null) {
            throw new \Exception(Text::_('COM_FLEXA_ENTRY_NOT_FOUND'), 404);
        }

        $this->entry = $entry;
        $this->setDocumentTitle((string) $entry->title);
        $this->document->getWebAssetManager()->useStyle('com_flexa.flexa');

        $pathway = $app->getPathway();
        $active = $app->getMenu()->getActive();

        if ($active && ($active->query['view'] ?? null) === 'entries') {
            $pathway->addItem((string) $entry->title);
        }

        parent::display($tpl);
    }
}
