<?php

namespace FlexaCms\Component\Flexa\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

\defined('_JEXEC') or exit;

class DisplayController extends BaseController
{
    protected $default_view = 'entries';

    public function display($cachable = false, $urlparams = [])
    {
        // Flexa responses are cached by the library; full-page caching stays with the site.
        return parent::display($cachable, ['entity' => 'CMD', 'slug' => 'STRING', 'limitstart' => 'UINT']);
    }
}
