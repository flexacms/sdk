=== Flexa ===
Contributors: flexacms
Tags: headless cms, content, api, blocks, shortcode
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show content from your Flexa headless CMS in WordPress with blocks, shortcodes and template functions.

== Description ==

Flexa is a headless CMS. This plugin brings its content into WordPress:

* **Blocks** — "Flexa entries" lists entries of a content model as a grid or list; "Flexa entry" shows one entry or a single field.
* **Shortcodes** — `[flexa_entries entity="posts"]` and `[flexa_entry entity="pages" slug="about"]`.
* **Template functions** — `flexa_entries()`, `flexa_entry()`, `flexa_value()` and `flexa_url()` for theme developers.
* **Translations** — translatable fields follow the WPML or Polylang language, or the site language.
* **Fast** — responses are cached in transients (or your object cache) and refreshed on a schedule you choose.
* **Themeable** — copy `templates/*.php` to `your-theme/flexa/` to change the markup.

= External service =

This plugin connects to the Flexa installation you configure (Settings → Flexa) to read content, sending your API key with each request. No data is sent anywhere else.

== Installation ==

1. Install and activate the plugin.
2. In Flexa, create an API key with the **Read** ability.
3. Go to **Settings → Flexa** and enter your Flexa URL and the API key. The status table lists the content models the key can read.

To keep the key out of the database, define it in `wp-config.php` instead:

`define( 'FLEXA_URL', 'https://cms.example.com' );`
`define( 'FLEXA_API_KEY', 'flx_...' );`

== Frequently Asked Questions ==

= How do I build a detail page for each entry? =

Create a page (for example `/post/`) with a "Flexa entry" block or `[flexa_entry entity="posts" slug_param="entry"]`. Then link your list to it: `[flexa_entries entity="posts" link="/post/?entry={slug}"]`.

= How do I show a single field? =

`[flexa_entry entity="pages" slug="about" field="headline"]`. Image fields render as images, file fields as links and rich text as HTML.

= Content changed in Flexa but not on my site =

Responses are cached (5 minutes by default). Lower the cache time or use **Clear Flexa cache** in Settings → Flexa.

= Can I use it in my theme? =

`
$posts = flexa_entries( 'posts', array( 'per_page' => 3 ) );
foreach ( $posts as $post ) {
    echo esc_html( flexa_value( $post, 'headline' ) );
    echo '<img src="' . esc_url( flexa_url( $post, 'cover' ) ) . '" alt="">';
}
`

== Changelog ==

= 0.2.0 =
* First release: blocks, shortcodes, template functions, transient cache and WPML/Polylang support.
