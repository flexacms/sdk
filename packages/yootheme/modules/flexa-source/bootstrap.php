<?php

namespace FlexaCms\YOOtheme;

// Loaded by WordPress or Joomla only.
if ( ! defined( 'ABSPATH' ) ) {
    defined('_JEXEC') or exit;
}


include_once __DIR__.'/src/Connection.php';
include_once __DIR__.'/src/TypeBuilder.php';
include_once __DIR__.'/src/Resolver.php';
include_once __DIR__.'/src/SourceListener.php';

return [
    'events' => [
        'source.init' => [
            SourceListener::class => ['initSource'],
        ],
    ],
];
