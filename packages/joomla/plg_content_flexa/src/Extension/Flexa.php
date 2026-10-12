<?php

namespace FlexaCms\Plugin\Content\Flexa\Extension;

use FlexaCms\Joomla\Core\Entry;
use FlexaCms\Joomla\Core\Exceptions\FlexaException;
use FlexaCms\Joomla\Flexa as FlexaClient;
use FlexaCms\Joomla\Html;
use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or exit;

/**
 * Replaces {flexa ...} tags in articles and modules with Flexa content:
 *
 *   {flexa entity="pages" slug="about" field="headline"}   one field
 *   {flexa entity="pages" slug="about"}                    a whole entry
 *   {flexa entity="posts" list="3" image="cover" text="excerpt"}   a list
 */
final class Flexa extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['onContentPrepare' => 'onContentPrepare'];
    }

    public function onContentPrepare(ContentPrepareEvent $event): void
    {
        if ($this->getApplication()->isClient('api') || $event->getContext() === 'com_finder.indexer') {
            return;
        }

        $item = $event->getItem();

        if (! isset($item->text) || ! str_contains((string) $item->text, '{flexa')) {
            return;
        }

        // A tag alone in its paragraph (as editors save it) replaces the whole
        // paragraph, so block output (lists, entries) is not nested in a <p>.
        $item->text = preg_replace_callback(
            '/<p>\s*\{flexa\s+([^}]*)\}\s*<\/p>|\{flexa\s+([^}]*)\}/i',
            function (array $match): string {
                $attributes = $this->attributes($match[2] ?? '' ?: $match[1]);
                $html = $this->render($attributes);
                $standalone = ($match[2] ?? '') === '';

                return $standalone && isset($attributes['field']) && $html !== '' ? '<p>'.$html.'</p>' : $html;
            },
            (string) $item->text,
        );
    }

    /**
     * @return array<string, string>
     */
    private function attributes(string $source): array
    {
        preg_match_all('/(\w+)\s*=\s*"([^"]*)"/', html_entity_decode($source, ENT_QUOTES, 'UTF-8'), $matches, PREG_SET_ORDER);

        return array_column(array_map(static fn (array $match): array => [strtolower($match[1]), trim($match[2])], $matches), 1, 0);
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function render(array $attributes): string
    {
        $client = FlexaClient::client();
        $entity = $attributes['entity'] ?? '';

        if ($client === null || $entity === '') {
            return '';
        }

        try {
            if (isset($attributes['list'])) {
                $entries = $client->entity($entity)->published()->list(1, max(1, min(50, (int) $attributes['list'])))->items;

                return $this->listHtml($entries, $attributes['image'] ?? '', $attributes['text'] ?? '');
            }

            $entry = isset($attributes['slug']) ? $client->entity($entity)->find($attributes['slug']) : null;
        } catch (FlexaException $exception) {
            Log::add('Flexa: '.$exception->getMessage(), Log::WARNING, 'plg_content_flexa');

            return '';
        }

        if ($entry === null) {
            return '';
        }

        if (isset($attributes['field'])) {
            return Html::field($entry, $attributes['field']);
        }

        return $this->entryHtml($entry, $attributes['image'] ?? '');
    }

    /**
     * @param  array<int, Entry>  $entries
     */
    private function listHtml(array $entries, string $imageField, string $textField): string
    {
        $this->useStyles();
        $html = '<div class="flexa-entries flexa-entries--list">';

        foreach ($entries as $entry) {
            $image = Html::firstUrl($entry, $imageField);
            $html .= '<article class="flexa-entry-card">';
            $html .= $image ? '<figure class="flexa-entry-card__media"><img src="'.htmlspecialchars($image, ENT_QUOTES, 'UTF-8').'" alt="" loading="lazy" decoding="async"></figure>' : '';
            $html .= '<div class="flexa-entry-card__body"><h3 class="flexa-entry-card__title">'.htmlspecialchars((string) $entry->title, ENT_QUOTES, 'UTF-8').'</h3>';
            $html .= $textField !== '' ? '<div class="flexa-entry-card__excerpt">'.Html::field($entry, $textField).'</div>' : '';
            $html .= '</div></article>';
        }

        return $html.'</div>';
    }

    private function entryHtml(Entry $entry, string $imageField): string
    {
        $this->useStyles();
        $image = Html::firstUrl($entry, $imageField);
        $html = '<div class="flexa-entry flexa-entry--'.htmlspecialchars($entry->entity, ENT_QUOTES, 'UTF-8').'">';
        $html .= $image ? '<figure class="flexa-entry__media"><img src="'.htmlspecialchars($image, ENT_QUOTES, 'UTF-8').'" alt="" decoding="async"></figure>' : '';

        foreach (array_keys($entry->values) as $key) {
            if ($key === $imageField || ($field = Html::field($entry, (string) $key)) === '') {
                continue;
            }

            $html .= '<div class="flexa-entry__field flexa-entry__field--'.htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8').'">'.$field.'</div>';
        }

        return $html.'</div>';
    }

    private function useStyles(): void
    {
        $document = $this->getApplication()->getDocument();

        if ($document === null || ! method_exists($document, 'getWebAssetManager')) {
            return;
        }

        $assets = $document->getWebAssetManager();
        $assets->getRegistry()->addExtensionRegistryFile('com_flexa');
        $assets->useStyle('com_flexa.flexa');
    }
}
