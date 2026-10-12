<?php

\defined('_JEXEC') or exit;

use FlexaCms\Component\Flexa\Site\View\Entries\HtmlView;
use FlexaCms\Joomla\Html;

/** @var HtmlView $this */
$layout = $this->params->get('layout_style', 'grid') === 'list' ? 'list' : 'grid';
$columns = max(1, min(6, (int) $this->params->get('columns', 3)));
$imageField = (string) $this->params->get('image_field', '');
$textField = (string) $this->params->get('text_field', '');
?>
<div class="com-flexa-entries">
    <?php if ($this->params->get('show_page_heading')) { ?>
        <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
    <?php } ?>

    <?php if ($this->problem !== null && $this->canSeeProblems()) { ?>
        <div class="flexa-notice"><strong>Flexa:</strong> <?php echo $this->escape($this->problem); ?></div>
    <?php } ?>

    <div class="flexa-entries flexa-entries--<?php echo $layout; ?>" style="--flexa-columns: <?php echo $columns; ?>">
        <?php foreach ($this->entries as $entry) { ?>
            <?php
            $link = $this->link($entry);
            $image = Html::firstUrl($entry, $imageField);
            ?>
            <article class="flexa-entry-card">
                <?php if ($image) { ?>
                    <figure class="flexa-entry-card__media">
                        <a href="<?php echo $link; ?>" tabindex="-1" aria-hidden="true">
                            <img src="<?php echo $this->escape($image); ?>" alt="" loading="lazy" decoding="async">
                        </a>
                    </figure>
                <?php } ?>
                <div class="flexa-entry-card__body">
                    <h2 class="flexa-entry-card__title h3"><a href="<?php echo $link; ?>"><?php echo $this->escape((string) $entry->title); ?></a></h2>
                    <?php if ($textField !== '') { ?>
                        <div class="flexa-entry-card__excerpt"><?php echo Html::field($entry, $textField); ?></div>
                    <?php } ?>
                </div>
            </article>
        <?php } ?>
    </div>

    <?php if ($this->pagination && $this->pagination->pagesTotal > 1) { ?>
        <div class="com-flexa-entries__pagination w-100">
            <?php echo $this->pagination->getPagesLinks(); ?>
        </div>
    <?php } ?>
</div>
