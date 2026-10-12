<?php

namespace FlexaCms\Component\Flexa\Administrator\Controller;

use FlexaCms\Joomla\Flexa;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or exit;

class DisplayController extends BaseController
{
    protected $default_view = 'dashboard';

    /**
     * Forget every cached Flexa response.
     */
    public function clearCache(): void
    {
        $this->checkToken();

        if (! $this->app->getIdentity()->authorise('core.manage', 'com_flexa')) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        Flexa::client()?->flushCache();

        $this->setRedirect(Route::_('index.php?option=com_flexa', false), Text::_('COM_FLEXA_CACHE_CLEARED'));
    }
}
