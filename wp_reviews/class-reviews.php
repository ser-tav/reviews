<?php
/*
Plugin Name: Отзывы
Plugin URI: https://github.com/ser-tav/reviews
Description: Плагин для добаления отзывов
Version: 1.0.0
Author: Sergey T.
Author URI: https://github.com/ser-tav/
License: GPLv2 or later
Text Domain: reviews
*/

if (!defined('ABSPATH')) {
    die;
}

class Reviews
{

    /**
     * Register actions
     * @return void
     */
    public function register()
    {

        // Register ost type
        add_action('init', [$this, 'custom_post_type']);

        // Enqueue
        add_action('wp_enqueue_scripts', [$this, 'enqueue_front']);

        // Add shortcode
        add_shortcode('reviews', [$this, 'add_shortcode']);

        // Rest data
        add_filter('rest_route_for_post', [$this, 'reviews_rest_route_for_post'], 10, 2);
        add_action('rest_api_init', [$this, 'register_meta_api']);

        // Meta boxes
        add_action('admin_menu', [$this, 'add_meta_box_for_reviews']);
        add_action('save_post', [$this, 'save_metadata'], 10, 2);
    }

    /**
     * On activating plugin
     * @return void
     */
    static function activation()
    {
        global $wpdb;

        $post_title = __('Страница с шорткодом отзывов', 'reviews');
        $post_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_title = '" . $post_title . "' AND post_status = 'publish'");

        $new_page = array(
            'post_title' => wp_strip_all_tags($post_title),
            'post_content' => '[reviews]',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'page',
        );

        if (!$post_id) {
            wp_insert_post($new_page);
        }

        //update rewrite rules
        flush_rewrite_rules();
        delete_option('rewrite_rules');
    }

    /**
     * On deactivating plugin
     * @return void
     */
    static function deactivation()
    {

        //update rewrite rules
        flush_rewrite_rules();
        delete_option('rewrite_rules');
    }

    /**
     * Enqueue Front
     * @return void
     */
    public function enqueue_front()
    {
        wp_enqueue_style('reviews-style', plugins_url('/assets/front/styles.css', __FILE__));
        wp_enqueue_script('reviews-script', plugins_url('/assets/front/scripts.js', __FILE__), array('jquery'), false, true);

        wp_localize_script('reviews-script', 'additionalData', array(
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'success' => __('Отзыв успешно добавлен!', 'reviews'),
            'failure' => __('* Пожалуйста заполните все обязательные поля', 'reviews'),
        ));
    }

    /**
     * Register CPT
     * @return void
     */
    public function custom_post_type()
    {
        register_post_type('review',
            [
                'public' => true,
                'has_archive' => true,
                'rewrite' => ['slug' => 'reviews'],
                'label' => esc_html__('Reviews', 'reviews'),
                'show_in_rest' => true,
                'rest_base' => 'review',
                'rest_controller_class' => 'WP_REST_Posts_Controller',
                'supports' => ['title', 'editor']
            ]
        );
    }

    /**
     * Add new route
     * @param $route
     * @param $post
     * @return mixed|string
     */
    public function reviews_rest_route_for_post($route, $post)
    {
        if ($post->post_type === 'review') {
            $route = '/wp/v2/review/' . $post->ID;
        }

        return $route;
    }

    /**
     * Register data from meta in rest
     * @return void
     */
    public function register_meta_api()
    {
        $meta_fields = array(
            'review_name',
            'review_social'
        );

        foreach ($meta_fields as $field) {
            register_rest_field('review',
                $field,
                array(
                    'get_callback' => array($this, 'get_meta'),
                    'update_callback' => array($this, 'update_meta'),
                    'show_in_rest' => true,
                    'schema' => null
                )
            );
        }
    }

    /**
     * @param $object
     * @param $field_name
     * @return mixed
     */
    public function get_meta($object, $field_name)
    {
        return get_post_meta($object['id'], $field_name);
    }

    /**
     * @param $value
     * @param $object
     * @param $field_name
     * @return bool|int|void
     */
    public function update_meta($value, $object, $field_name)
    {
        if (!$value || !is_string($value)) {
            return;
        }

        return update_post_meta($object->ID, $field_name, maybe_serialize(strip_tags($value)));
    }

    /**
     * Add meta box
     * @return void
     */
    public function add_meta_box_for_reviews()
    {
        add_meta_box(
            'reviews-info',
            __('Данные отправителя', 'reviews'),
            [$this, 'meta_box_html'],
            'review',
            'normal',
            'default'
        );
    }

    /**
     * Meta box template
     * @param $post
     * @return void
     */
    public function meta_box_html($post)
    {
        wp_nonce_field('reviewnoncefields', '_reviews');

        $name = get_post_meta($post->ID, 'review_name', true);
        $social = get_post_meta($post->ID, 'review_social', true);

        echo '<table class="form-table">
                <tbody>
                    <tr>
                        <th><label for="review_name">' . esc_html__('Имя', 'reviews') . '</label></th>
                        <td><input type="text" id="review_name" name="review_name" value="' . esc_attr($name) . '" /></td>
                    </tr>
                    <tr>
                        <th><label for="review_social">' . esc_html__('Ссылка на соцсети', 'reviews') . '</label></th>
                        <td><input type="url" id="review_social" name="review_social" value="' . esc_attr($social) . '" /></td>
                    </tr>
                </tbody>
                </table>';
    }

    /**
     * Save meta data
     * @param $post_id
     * @param $post
     * @return mixed
     */
    public function save_metadata($post_id, $post)
    {

        if (!isset($_POST['_reviews']) || !wp_verify_nonce($_POST['_reviews'], 'reviewnoncefields')) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if ($post->post_type != 'review') {
            return $post_id;
        }

        $post_type = get_post_type_object($post->post_type);
        if (!current_user_can($post_type->cap->edit_post, $post_id)) {
            return $post_id;
        }

        if (is_null($_POST['review_name'])) {
            delete_post_meta($post_id, 'review_name');
        } else {
            update_post_meta($post_id, 'review_name', sanitize_text_field($_POST['review_name']));
        }

        if (is_null($_POST['review_social'])) {
            delete_post_meta($post_id, 'review_social');
        } else {
            update_post_meta($post_id, 'review_social', sanitize_text_field($_POST['review_social']));
        }

        return $post_id;
    }

    /**
     * Create shortcode
     * usage - [reviews]
     * @return false|string
     */
    public function add_shortcode()
    {
        ob_start();

        require_once plugin_dir_path(__FILE__) . 'templates/review-form-template.php';

        return ob_get_clean();
    }
}

if (class_exists('Reviews')) {
    $reviews = new Reviews();
    $reviews->register();
}

register_activation_hook(__FILE__, array($reviews, 'activation'));
register_deactivation_hook(__FILE__, array($reviews, 'deactivation'));
