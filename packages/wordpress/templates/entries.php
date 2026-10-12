<?php
/**
 * A list of Flexa entries. Copy to {your-theme}/flexa/entries.php to customize.
 *
 * @var array<int, Entry> $entries
 * @var Page $page
 * @var array<string, mixed> $args
 */

use FlexaCms\WordPress\Core\Entry;
use FlexaCms\WordPress\Core\Page;
use FlexaCms\WordPress\Renderer;

if (! defined('ABSPATH')) {
    exit;
}

$flexa_layout = $args['layout'] === 'list' ? 'list' : 'grid';
$flexa_columns = max(1, min(6, (int) $args['columns']));
?>
<div class="flexa-entries flexa-entries--<?php echo esc_attr($flexa_layout); ?> <?php echo esc_attr((string) $args['class']); ?>" style="--flexa-columns: <?php echo (int) $flexa_columns; ?>">
    <?php foreach ($entries as $flexa_entry) { ?>
        <?php
        $flexa_link = Renderer::link($flexa_entry, (string) $args['link']);
        $flexa_image = $args['image'] !== '' ? $flexa_entry->url((string) $args['image']) : null;
        $flexa_image = is_array($flexa_image) ? ($flexa_image[0] ?? null) : $flexa_image;
        ?>
        <article class="flexa-entry-card">
            <?php if ($flexa_image) { ?>
                <figure class="flexa-entry-card__media">
                    <?php if ($flexa_link) { ?><a href="<?php echo esc_url($flexa_link); ?>" tabindex="-1" aria-hidden="true"><?php } ?>
                    <img src="<?php echo esc_url($flexa_image); ?>" alt="" loading="lazy" decoding="async" />
                    <?php if ($flexa_link) { ?></a><?php } ?>
                </figure>
            <?php } ?>
            <div class="flexa-entry-card__body">
                <h3 class="flexa-entry-card__title">
                    <?php if ($flexa_link) { ?>
                        <a href="<?php echo esc_url($flexa_link); ?>"><?php echo esc_html((string) $flexa_entry->title); ?></a>
                    <?php } else { ?>
                        <?php echo esc_html((string) $flexa_entry->title); ?>
                    <?php } ?>
                </h3>
                <?php if ($args['excerpt'] !== '') { ?>
                    <div class="flexa-entry-card__excerpt"><?php echo Renderer::field($flexa_entry, (string) $args['excerpt']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by Renderer::field().?></div>
                <?php } ?>
            </div>
        </article>
    <?php } ?>
</div>
