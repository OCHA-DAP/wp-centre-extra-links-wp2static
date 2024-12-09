<?php
/*
Plugin Name: HDX Extra Links for WP2Static
Description: Adds extra links to the WP2Static plugin
Version: 1.0.1
Author: https://centre.humdata.org
Author URI: https://centre.humdata.org
*/

// Define the plugin namespace
namespace HDX\ExtraLinksForWP2Static;

// Define the plugin class
class Plugin {
    // Define the plugin hooks
    public function __construct() {
        add_filter('wp2static_modify_initial_crawl_list', array($this, 'add_custom_wp2static_urls'));
    }

    // Function to list UFAQ categories and their sub-categories
    public function list_faq_categories() {
        $categories = get_terms(array('taxonomy' => 'ufaq-category', 'hide_empty' => false, 'parent' => 0));
        $result = array();

        foreach ($categories as $category) {
            // Array to hold sub-category IDs
            $sub_cat_ids = array();

            $result[] = '/custom-ufaq-category/'.$category->term_id.'.json';
            // Fetch sub-categories
            $sub_categories = get_terms(array('taxonomy' => 'ufaq-category', 'hide_empty' => false, 'parent' => $category->term_id));
            foreach ($sub_categories as $sub_cat) {
                $sub_cat_ids[] = $sub_cat->term_id;
            }

            if (!empty($sub_cat_ids)) {
                // Prepare API URL for sub-categories
                $sub_cat_ids_string = implode(',', $sub_cat_ids);
                $custom_api_url = '/custom-ufaq-list/' . $sub_cat_ids_string.'.json';
                $result[] = $custom_api_url;
            }
        }

        return $result;
    }

    // Function to get Redirection plugin redirects
    public function get_redirection_plugin_redirects() {
        global $wpdb;

        // Query to get active redirects
        $results = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}redirection_items
            WHERE status = 'enabled' AND regex != 1
        ");

        $redirects = [];

        // Check if there are any results
        if (!empty($results)) {
            foreach ($results as $redirect) {
                $redirects[] = array(
                    'source' => esc_html($redirect->url),
                    'target' => esc_html($redirect->target),
                );
            }
        }

        return $redirects;
    }

    // Function to add custom WP2Static URLs
    public function add_custom_wp2static_urls($url_queue) {
        $custom_urls = [
            '/wp-content/themes/uncode-child/style.css.map',
            '/wp-content/themes/uncode-child/js/humdata-footer.js.map',
            '/wp-admin/js/password-strength-meter.min.js',
            '/dataviz/',
            '/hdx_logos/',
            '/stateofdata2024/',
            '/stateofdata2023/',
            '/stateofdata2022/',
            '/brochure/',
            '/peer-review-framework/',
            '/stateofdata2021/',
            '/documentation/guide/',
            '/learning-BYTES/quick-tips-for-visualising-data/'
        ];

        // Automatically retrieve source URLs from the Redirection plugin
        $redirects = $this->get_redirection_plugin_redirects();
        foreach ($redirects as $redirect) {
            $source_url = rtrim($redirect['source'], '/');
            $custom_urls[] = $source_url . '/';
        }

        // Function to list UFAQ categories and their sub-categories
        $faq_urls = $this->list_faq_categories();

        // Merge all URLs with the existing URL queue
        $url_queue = array_merge($url_queue, $custom_urls, $faq_urls);

        return $url_queue;
    }
}

// Initialize the plugin
$plugin = new Plugin();
