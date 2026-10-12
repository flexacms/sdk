<?php
/**
 * One Flexa entry with all its fields. Copy to {your-theme}/flexa/entry.php to customize.
 *
 * @var Entry $entry
 * @var array<string, mixed> $args
 */

use FlexaCms\WordPress\Core\Entry;
use FlexaCms\WordPress\Renderer;

if (! defined('ABSPATH')) {
    exit;
}

$flexa_image = $args['image'] !== '' ? $entry->url((string) $args['image']) : null;
$flexa_image = is_array($flexa_image) ? ($flexa_image[0] ?? null) : $flexa_image;
?>
<article class="flexa-entry flexa-entry--<?php echo esc_attr($entry->entity); ?> <?php echo esc_attr((string) $args['class']); ?>">
    <h2 class="flexa-entry__title"><?php echo esc_html((string) $entry->title); ?></h2>

    <?php if ($flexa_image) { ?>
        <figure class="flexa-entry__media"><img src="<?php echo esc_url($flexa_image); ?>" alt="<?php echo esc_attr((string) $entry->title); ?>" decoding="async" /></figure>
    <?php } ?>

    <?php foreach (array_keys($entry->values) as $flexa_key) { ?>
        <?php
        if ($flexa_key === $args['image']) {
            continue;
        }

        $flexa_html = Renderer::field($entry, (string) $flexa_key);
        ?>
        <?php if ($flexa_html !== '') { ?>
            <div class="flexa-entry__field flexa-entry__field--<?php echo esc_attr(sanitize_html_class((string) $flexa_key)); ?>">
                <?php echo $flexa_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by Renderer::field().?>
            </div>
        <?php } ?>
    <?php } ?>
</article>
