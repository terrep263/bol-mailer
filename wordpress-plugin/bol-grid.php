<?php
/**
 * Plugin Name: BOL Category Grid
 * Description: Renders 1 latest post per category in a true 4-column grid via [bol_grid] shortcode
 * Version: 1.1.0
 * Author: The Book of Lies
 */

if (!defined('ABSPATH')) exit;

add_shortcode('bol_grid', 'bol_render_category_grid');

function bol_render_category_grid() {
    // Faith=4, Love=5, Money=6, Relationships=7, Institutions=9
    $cats = [
        ['id' => 4, 'label' => 'FAITH',         'slug' => 'faith'],
        ['id' => 5, 'label' => 'LOVE',          'slug' => 'love'],
        ['id' => 6, 'label' => 'MONEY',         'slug' => 'money'],
        ['id' => 7, 'label' => 'RELATIONSHIPS', 'slug' => 'relationships'],
    ];

    $uid = 'bolgrid' . substr(md5(uniqid()), 0, 6);

    ob_start();
    echo '<style>
.bol-grid-'.$uid.' {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    width: 100%;
}
.bol-grid-'.$uid.' .bol-card {
    background: #ffffff;
    border: 1px solid #dddddd;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    transition: box-shadow 0.2s;
}
.bol-grid-'.$uid.' .bol-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}
.bol-grid-'.$uid.' .bol-card a {
    text-decoration: none;
    color: inherit;
}
.bol-grid-'.$uid.' .bol-card-thumb {
    width: 100%;
    aspect-ratio: 16/9;
    object-fit: cover;
    display: block;
    background: #f0ede8;
}
.bol-grid-'.$uid.' .bol-card-thumb-placeholder {
    width: 100%;
    aspect-ratio: 16/9;
    background: #1b2a4a;
    display: flex;
    align-items: center;
    justify-content: center;
}
.bol-grid-'.$uid.' .bol-card-thumb-placeholder span {
    font-family: "Barlow Condensed", Arial Narrow, sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 3px;
    color: #c9a84c;
    text-transform: uppercase;
}
.bol-grid-'.$uid.' .bol-card-body {
    padding: 16px;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.bol-grid-'.$uid.' .bol-card-cat {
    font-family: "Barlow Condensed", Arial Narrow, sans-serif;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 3px;
    color: #B22234;
    text-transform: uppercase;
    margin: 0 0 8px;
}
.bol-grid-'.$uid.' .bol-card-title {
    font-family: "Playfair Display", Georgia, serif;
    font-size: 16px;
    font-weight: 700;
    color: #111111;
    line-height: 1.35em;
    margin: 0 0 10px;
}
.bol-grid-'.$uid.' .bol-card-excerpt {
    font-size: 13px;
    color: #666666;
    line-height: 1.6em;
    margin: 0 0 14px;
    flex: 1;
}
.bol-grid-'.$uid.' .bol-card-link {
    font-family: "Barlow Condensed", Arial Narrow, sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    color: #B22234;
    text-transform: uppercase;
    text-decoration: none;
}
.bol-grid-'.$uid.' .bol-card-link:hover { text-decoration: underline; }
@media (max-width: 900px) {
    .bol-grid-'.$uid.' { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 520px) {
    .bol-grid-'.$uid.' { grid-template-columns: 1fr; }
}
</style>';

    echo '<div class="bol-grid-'.$uid.'">';

    foreach ($cats as $cat) {
        $posts = get_posts([
            'numberposts'      => 1,
            'category'         => $cat['id'],
            'post_status'      => 'publish',
            'suppress_filters' => false,
        ]);

        if (empty($posts)) continue;

        $post      = $posts[0];
        $title     = get_the_title($post);
        $permalink = get_permalink($post);
        $excerpt   = wp_trim_words(get_the_excerpt($post), 18, '...');
        $thumb_id  = get_post_thumbnail_id($post->ID);
        $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'medium') : '';

        echo '<div class="bol-card">';
        echo '<a href="'.esc_url($permalink).'">';
        if ($thumb_url) {
            echo '<img class="bol-card-thumb" src="'.esc_url($thumb_url).'" alt="'.esc_attr($title).'" loading="lazy" />';
        } else {
            echo '<div class="bol-card-thumb-placeholder"><span>'.$cat['label'].'</span></div>';
        }
        echo '</a>';
        echo '<div class="bol-card-body">';
        echo '<p class="bol-card-cat">'.esc_html($cat['label']).'</p>';
        echo '<h3 class="bol-card-title"><a href="'.esc_url($permalink).'">'.esc_html($title).'</a></h3>';
        if ($excerpt) echo '<p class="bol-card-excerpt">'.esc_html($excerpt).'</p>';
        echo '<a class="bol-card-link" href="'.esc_url($permalink).'">Read More &rarr;</a>';
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
    return ob_get_clean();
}
