<?php

namespace FlexaCms\Component\Flexa\Site\Service;

use Joomla\CMS\Component\Router\RouterBase;

\defined('_JEXEC') or exit;

/**
 * SEF URLs: an entry under a "Flexa: entries" menu item gets its slug as the
 * last segment, e.g. /blog/hello-world.
 */
class Router extends RouterBase
{
    public function build(&$query)
    {
        $segments = [];
        $menuQuery = isset($query['Itemid']) ? ($this->menu->getItem($query['Itemid'])?->query ?? []) : [];
        $view = $query['view'] ?? null;

        if ($view === 'entry' && isset($query['slug'])) {
            if (($menuQuery['view'] ?? null) === 'entry' && ($menuQuery['slug'] ?? null) === $query['slug']) {
                unset($query['view'], $query['slug'], $query['entity']);
            } elseif (($menuQuery['view'] ?? null) === 'entries' && ($menuQuery['entity'] ?? null) === ($query['entity'] ?? null)) {
                $segments[] = $query['slug'];
                unset($query['view'], $query['slug'], $query['entity']);
            }
        } elseif ($view === 'entries' && ($menuQuery['view'] ?? null) === 'entries') {
            unset($query['view'], $query['entity']);
        }

        return $segments;
    }

    public function parse(&$segments)
    {
        $active = $this->menu->getActive();

        if ($segments === [] || ! $active || ($active->query['view'] ?? null) !== 'entries') {
            return [];
        }

        $slug = (string) array_shift($segments);
        $segments = [];

        return ['view' => 'entry', 'entity' => (string) ($active->query['entity'] ?? ''), 'slug' => $slug];
    }
}
