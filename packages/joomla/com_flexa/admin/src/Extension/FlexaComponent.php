<?php

namespace FlexaCms\Component\Flexa\Administrator\Extension;

use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\MVCComponent;

\defined('_JEXEC') or exit;

class FlexaComponent extends MVCComponent implements RouterServiceInterface
{
    use RouterServiceTrait;
}
