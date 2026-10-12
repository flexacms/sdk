<?php

namespace FlexaCms\Component\Flexa\Administrator\View\Dashboard;

use FlexaCms\Joomla\Core\Exceptions\FlexaException;
use FlexaCms\Joomla\Flexa;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') or exit;

/**
 * Connection status, available content models and how to use them.
 */
class HtmlView extends BaseHtmlView
{
    public bool $configured = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $entities = [];

    public ?string $error = null;

    public function display($tpl = null): void
    {
        $client = Flexa::client();
        $this->configured = $client !== null;

        if ($client !== null) {
            try {
                $this->entities = $client->entities();
            } catch (FlexaException $exception) {
                $this->error = $exception->getMessage();
            }
        }

        ToolbarHelper::title(Text::_('COM_FLEXA'), 'file-alt');

        if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_flexa')) {
            ToolbarHelper::preferences('com_flexa');
        }

        parent::display($tpl);
    }
}
