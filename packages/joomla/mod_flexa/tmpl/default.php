<?php

\defined('_JEXEC') or exit;

use FlexaCms\Joomla\Core\Entry;
use FlexaCms\Joomla\Html;
use Joomla\Registry\Registry;

/**
 * @var Registry $params
 * @var array<int, Entry> $entries
 * @var callable $link
 * @var string|null $problem
 * @var bool $canSeeProblems
 */
if ($problem !== null && $canSeeProblems) {
    echo '<div class="flexa-notice"><strong>Flexa:</strong> '.htmlspecialchars($problem, ENT_QUOTES, 'UTF-8').'</div>';
}

if ($entries === []) {
    return;
}

$layout = $params->get('layout_style', 'list') === 'grid' ? 'grid' : 'list';
$columns = max(1, min(6, (int) $params->get('columns', 3)));
$imageField = (string) $params->get('image_field', '');
$textField = (string) $params->get('text_field', '');
?>
<div class="mod-flexa flexa-entries flexa-entries--<?php echo $layout; ?>" style="--flexa-columns: <?php echo $columns; ?>">
    <?php foreach ($entries as $entry) { ?>
        <?php
        $url = $link($entry);
        $image = Html::firstUrl($entry, $imageField);
        ?>
        <article class="flexa-entry-card">
            <?php if ($image) { ?>
                <figure class="flexa-entry-card__media">
                    <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="" loading="lazy" decoding="async">
                </figure>
            <?php } ?>
            <div class="flexa-entry-card__body">
                <h3 class="flexa-entry-card__title">
                    <?php if ($url) { ?>
                        <a href="<?php echo $url; ?>"><?php echo htmlspecialchars((string) $entry->title, ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php } else { ?>
                        <?php echo htmlspecialchars((string) $entry->title, ENT_QUOTES, 'UTF-8'); ?>
                    <?php } ?>
                </h3>
                <?php if ($textField !== '') { ?>
                    <div class="flexa-entry-card__excerpt"><?php echo Html::field($entry, $textField); ?></div>
                <?php } ?>
            </div>
        </article>
    <?php } ?>
</div>
