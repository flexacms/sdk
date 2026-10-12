<?php

namespace FlexaCms\Component\Flexa\Site\View\Entries;

use FlexaCms\Joomla\Core\Entry;
use FlexaCms\Joomla\Core\Exceptions\FlexaException;
use FlexaCms\Joomla\Flexa;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

\defined('_JEXEC') or exit;

/**
 * A page of entries of one content model (menu item "Flexa: entries").
 */
class HtmlView extends BaseHtmlView
{
    public string $entity = '';

    /**
     * @var array<int, Entry>
     */
    public array $entries = [];

    public ?Pagination $pagination = null;

    public Registry $params;

    public ?string $problem = null;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $this->params = $app->getParams();
        $this->entity = $app->getInput()->getCmd('entity', (string) $this->params->get('entity', ''));
        $perPage = max(1, min(100, (int) $this->params->get('per_page', 12)));
        $start = $app->getInput()->getUint('limitstart', 0);
        $client = Flexa::client();

        if ($client === null || $this->entity === '') {
            $this->problem = Text::_('COM_FLEXA_NOT_READY');
        } else {
            try {
                $page = $client->entity($this->entity)->published()->list(intdiv($start, $perPage) + 1, $perPage);
                $this->entries = $page->items;
                $this->pagination = new Pagination($page->total, $start, $perPage);
            } catch (FlexaException $exception) {
                Log::add('Flexa: '.$exception->getMessage(), Log::WARNING, 'com_flexa');
                $this->problem = $exception->getMessage();
            }
        }

        $this->document->getWebAssetManager()->useStyle('com_flexa.flexa');

        if (! $this->params->get('page_title') && ($menu = $app->getMenu()->getActive())) {
            $this->setDocumentTitle($menu->title);
        }

        parent::display($tpl);
    }

    /**
     * Link of an entry's own page, under the current menu item.
     */
    public function link(Entry $entry): string
    {
        $itemId = Factory::getApplication()->getMenu()->getActive()?->id;

        return Route::_('index.php?option=com_flexa&view=entry&entity='.rawurlencode($this->entity).'&slug='.rawurlencode((string) ($entry->slug ?? $entry->id)).($itemId ? '&Itemid='.$itemId : ''));
    }

    /**
     * Problems are shown to people who can manage Flexa; visitors see nothing.
     */
    public function canSeeProblems(): bool
    {
        return Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_flexa');
    }
}
