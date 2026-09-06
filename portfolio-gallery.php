<?php
/**
 * Plugin Name: Portfolio Gallery
 * Description: A responsive design portfolio gallery with filtering functionality.
 * Adds a "Portfolio Item" post type, a "Portfolio Tag" taxonomy, and a [portfolio_gallery] shortcode.
 * Version: 1.0.0
 * Author: ardentalliance
 */

// No direct access
if (! defined('ABSPATH') ) {
    exit;
}

define( 'PORTFOLIO_GALLERY_VERSION', '1.0.0' );
define( 'PORTFOLIO_GALLERY_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register the "Portfolio Item" custom post type.
 * Each item = one piece in the portfolio (title, description, ft. image)
 */
function portfolio_gallery_register_post_type() {
    register_post_type( 'portfolio_item', array(
        'labels' => array(
            'name' => 'Portfolio Items',
            'singular_name' => 'Portfolio Item',
            'add_new_item' => 'Add Portfolio Item',
            'edit_item' => 'Edit Portfolio Item',
            'all_items' => 'All Portfolio Items',
            'featured_item' => 'Featured Portfolio Item',
            'not_found' => 'Portfolio Item Not Found',
            'set_featured_image' => 'Set Featured Image',
        ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-format-image',
        'menu_position' => 5,
        'supports' => array( 'title', 'thumbnail', 'editor' ),
        'show_in_rest' => true,
        'rewrite' => array( 'slug' => 'portfolio' ),
    ));
}
add_action( 'init', 'portfolio_gallery_register_post_type' );

/**
 * Register the "Portfolio Tag" taxonomy.
 * Non-hierarchical (like tags) so one item can carry several. (E.g. "UI/UX", "Illustration")
 * The same terms double as the filter buttons and the category labels shown on each item.
 */
function portfolio_gallery_register_taxonomy()
{
    register_taxonomy('portfolio_item', 'portfolio_item', array(
        'labels' => array(
            'name' => 'Portfolio Tags',
            'singular_name' => 'Portfolio Tag',
            'search_items' => 'Search Tags',
            'all_items' => 'All Tags',
            'edit_item' => 'Edit Tag',
            'update_item' => 'Update Tag',
            'add_new_item' => 'Add New Tag',
            'new_item_name' => 'New Tag Name',
            'menu_name' => 'Tags',
        ),
        'hierarchical' => true,
        'public' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
    ));
}
add_action( 'init', 'portfolio_gallery_register_taxonomy' );

/**
 * Custom image sizes: a smaller one for gallery grid preview images,
 * and a larger one for the lightbox full view. Proportional without a server-side hard crop,
 * in order to account for unusual aspect ratios for custom illustrations and designs.
 * Grid's CSS `object-fit: cover` handles visual crop in the browser.
 *
 * Existing Portfolio Item images already uploaded to WordPress will either need the Regenerate Thumbnails plugin
 * or a reupload in order to get the new thumbnail sizes.
 */
function portfolio_gallery_register_image_sizes() {
    add_image_size( 'portfolio_grid', 600, 0, false );
    add_image_size( 'portfolio_lightbox', 1600, 0, false );
}
add_action( 'init', 'portfolio_gallery_register_image_sizes' );

/**
 * Flush rewrite rules once on activation/deactivation so /portfolio/... URLs behave themselves :)
 */
function portfolio_gallery_activate() {
    portfolio_gallery_register_post_type();
    portfolio_gallery_register_taxonomy();
    portfolio_gallery_register_image_sizes();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'portfolio_gallery_activate' );

function portfolio_gallery_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'portfolio_gallery_deactivate' );

/**
 * Register the gallery's CSS/JS. Only loaded on pages that use the
 * [portfolio_gallery] shortcode.
 */
function portfolio_gallery_register_assets() {
    wp_register_style(
        'portfolio-gallery',
        PORTFOLIO_GALLERY_URL . 'assets/portfolio-gallery.css',
        array(),
        PORTFOLIO_GALLERY_VERSION
    );
    wp_register_script(
        'portfolio-gallery',
        PORTFOLIO_GALLERY_URL . 'assets/portfolio-gallery.js',
        array(),
        PORTFOLIO_GALLERY_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'portfolio_gallery_register_assets' );

/**
 * The [portfolio_gallery] shortcode.
 * Usage: [portfolio_gallery]
 *        [portfolio_gallery tag="illustration"] -- pre-filtered to one tag on load
 */
function portfolio_gallery_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'tag' => '',
        'limit' => -1,
    ), $atts, 'portfolio_gallery' );

    wp_enqueue_style( 'portfolio-gallery' );
    wp_enqueue_script( 'portfolio-gallery' );

    $query_args = array(
        'post_type' => 'portfolio_item',
        'posts_per_page' => intval( $atts['limit']),
        'orderby' => 'date',
        'order' => 'DESC',
    );

    if ( ! empty( $atts['tag'] ) ) {
        $query_args['tax_query'] = array( array(
            'taxonomy' => 'portfolio_tag',
            'field' => 'slug',
            'terms' => sanitize_title( $atts['tag'] ),
        ) );
    }

    $items = new WP_Query( $query_args );
    $all_terms = get_terms( array(
        'taxonomy' => 'portfolio_tag',
        'hide_empty' => true,
    ) );

    ob_start();
    ?>
    <div class="portfolio-gallery">
        <?php if ( ! is_wp_error( $all_terms ) && ! empty( $all_terms ) ) : ?>
            <div class="portfolio-gallery__filters" role="group" aria-label="Filter portfolio by category">
                <button type="button" class="portfolio-gallery__filter is-active" data-filter="all">
                    All</button>
                <?php foreach ( $all_terms as $term ) : ?>
                    <button type="button" class="portfolio-gallery__filter" data-filter="
                        <?php echo esc_attr( $term->slug ); ?>">
                        <?php echo esc_html( $term->name ); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="portfolio-gallery__grid">
            <?php if ( $items->have_posts() ) : while ( $items->have_posts() ) : $items->the_post(); ?>
            <?php
            $terms = get_the_terms( get_the_ID(), 'portfolio_tag' );
            $slugs = array();
            $names = array();
            if ( $terms && ! is_wp_error( $terms )) {
                foreach ( $terms as $term ) {
                    $slugs[] = $term->slug;
                    $names[] = $term->name;
                }
            }
            ?>
            <?php $lightbox_image_url = get_the_post_thumbnail_url( get_the_ID(),
            'portfolio_lightbox' ); ?>
            <article class="portfolio-gallery__item" data-tags="<?php echo esc_attr(
                implode( ' ', $slugs ) ); ?>">
                <button
                    type="button"
                    class="portfolio-gallery__trigger"
                    data-full="<?php echo esc_url( $lightbox_image_url ?
                    $lightbox_image_url : ''); ?>"
                    data-title="<?php echo esc_attr( get_the_title() ); ?>"
                    data-tags-label="<?php echo esc_attr( implode( '·', $names ) ); ?>"
                    data-permalink="<?php echo esc_url( get_permalink() ); ?>"
                    aria-label="<?php echo esc_attr( sprintf ('View %s larger', get_the_title()
                    ) ); ?>"
                 >
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'portfolio_grid', array( 'loading'
                        => 'lazy', 'alt' => get_the_title() ) ); ?>
                    <?php endif; ?>
                </button>
                <div class="portfolio-gallery__caption">
                    <span class="portfolio-gallery__title"><?php the_title(); ?></span>
                    <?php if ( !empty( $names ) ) : ?>
                        <span class="portfolio-gallery__tags"><?php echo esc_html(
                            implode( '·', $names ) ); ?></span>
                    <?php endif; ?>
                    <a href="<?php the_permalink(); ?>" class="portfolio-gallery__more">
                        See more info $rarr;
                    </a>
                </div>
            </article>
        <?php endwhile; wp_reset_postdata(); endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'portfolio_gallery', 'portfolio_gallery_shortcode' );