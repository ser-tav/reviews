<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

$reviews = get_posts(
    array(
        'post_type' => 'review',
        'numberposts' => -1
    )
);
foreach ($reviews as $key => $review) {
    wp_delete_post($review->ID, true);
    delete_post_meta($review->ID, $key);
}