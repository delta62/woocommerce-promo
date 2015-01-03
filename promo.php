<?php
/**
 * Plugin Name: Promo Shortcode
 * Plugin URI: https://samnoedel.com
 * Description: Easily embed featured products onto pages using shortcode
 * Version: 0.1
 * Author: Sam Singer Noedel
 */

defined('ABSPATH') or die('No script kiddies please!');

class Promo {

    private static $defaults = array(
        'title'    => '',
        'category' => '',
        'sort'     => 'date',
        'order'    => 'desc',
        'count'    => 4,
        'class'    => '',
        'text'     => 'first' /* random, first, last */
    );

    private $options = array();

    public function shortcode($attrs, $content) {
        $this->get_options($attrs);
        $products = $this->get_products();
        $output = '';

        if ($content || $products) {
            $output .= "<section class='promo {$this->options['class']}'>";
        }

        // Insert content text randomly
        if ($content) {
            $content_position = $this->get_content_position();
            array_splice($products, $content_position, 0, $content);
        }

        if ($this->options['title']) {
            $output .= $this->format_title();
        }
        $output .= $this->format_products($products);

        if ($content || $products) {
            $output .= '</section>';
        }

        return $output;
    }

    private function get_content_position() {
        switch ($this->options['text']) {
        case 'first':
            return 0;
        case 'last':
            return $this->options['count'];
        default:
            return rand(0, $this->options['count']);
        }
    }

    private function get_options($overrides) {
        $this->options = shortcode_atts(self::$defaults, $overrides);
    }

    /**
     * Get an array of WP_Post objects for the relevant products
     */
    private function get_products() {
        return get_posts(array(
            'post_type'       => array('product', 'product_variation'),
            'posts_per_page'  => $this->options['count'],
            'orderby'         => $this->options['sort'],
            'order'           => $this->options['order'],
            'category'        => $this->options['category'],
            'post_status'     => 'publish',
            'meta_query'      => array(
                array(
                    'key'     => '_visibility',
                    'value'   => array('catalog', 'visible'),
                    'compare' => 'IN'
                ),
                array(
                    'key'     => '_featured',
                    'value'   => 'yes'
                )
            ))
        );
    }

    private function format_title() {
        return '<h2 class="promo-title">' . $this->options['title'] . '</h2>';
    }

    private function format_products($products) {
        $output = '<div class="promo-items">';

        foreach ($products as $product) {
            if ($product instanceof WP_Post) {
                $output .= $this->format_product($product);
            } else {
                $output .= $this->format_content($product);
            }
        }

        $output .= '</div>';

        return $output;
    }

    private function format_product($product) {
        $output = '<div class="promo-item">';

        $thumb_id = get_post_thumbnail_id($product->ID);
        if ($thumb_id) {
            $src = wp_get_attachment_image_src($thumb_id, 'medium', TRUE);

            $output .= '<div class="promo-thumb">';
            $output .= '<a href="' . get_permalink($product->ID) . '">';

            $output .= "<img src='{$src[0]}' alt='{$product->post_title} class='promo-image' />";

            $output .= '</a>';
            $output .= '</div>';
        }

        $output .= '<a href="' . get_permalink($product->ID) . '">';
        $output .= '<strong class="promo-caption">' . $product->post_title . '</strong>';
        $output .= '</a>';
        $output .= '</div>';

        return $output;
    }

    private function format_content($content) {
        $output = '<div class="promo-item promo-text">';
        $output .= $content;
        return $output . '</div>';
    }

}

add_shortcode('promo', array(new Promo(), 'shortcode'));
