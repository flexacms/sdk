<?php

\defined('_JEXEC') or exit;

use FlexaCms\Component\Flexa\Site\View\Entry\HtmlView;
use FlexaCms\Joomla\Html;

/** @var HtmlView $this */
$entry = $this->entry;
$imageField = (string) $this->params->get('image_field', '');
$image = Html::firstUrl($entry, $imageField);
?>
<article class="com-flexa-entry flexa-entry flexa-entry--<?php echo $this->escape($entry->entity); ?>">
    <h1 class="flexa-entry__title"><?php echo $this->escape((string) $entry->title); ?></h1>

    <?php if ($image) { ?>
        <figure class="flexa-entry__media"><img src="<?php echo $this->escape($image); ?>" alt="<?php echo $this->escape((string) $entry->title); ?>" decoding="async"></figure>
    <?php } ?>

    <?php foreach (array_keys($entry->values) as $key) { ?>
        <?php
        if ($key === $imageField) {
            continue;
        }

        $html = Html::field($entry, (string) $key);
        ?>
        <?php if ($html !== '') { ?>
            <div class="flexa-entry__field flexa-entry__field--<?php echo $this->escape((string) $key); ?>"><?php echo $html; ?></div>
        <?php } ?>
    <?php } ?>
</article>
