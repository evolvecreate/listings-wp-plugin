<?php

    class listings {

        public $api;
        public $attributes = array();

        public $searchOptions = array();
        protected $searchOptionNames = array('bedrooms', 'baths', 'priceMin', 'priceMax', 'city', 'subdivision', 'style', 'limit');

        public function __construct(sparkAPI $sparkAPI, $displayErrors = false, $memory = '256M') {

            $this->api = $sparkAPI;

            $this->set_memory_limit($memory);

            if ($displayErrors) {
                $this->set_display_errors();
            }

            // $this->init();
        }

        private function set_display_errors() {

            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL ^E_NOTICE);
        }

        private function set_memory_limit($limit) {

            ini_set('memory_limit', $limit); // originally 40MB usage with a 42MB limit
        }


        public function init() {

            $this->initialize_shortcodes();
            $this->initialize_ajax_actions();
            $this->initialize_custom_post_types();

            $this->initialize_options_and_rewrite_rules();
            $this->initialize_scripts();
            $this->initialize_styles();
        }

        private function initialize_shortcodes() {

            add_shortcode('real-estate-listings', array($this, 'shortcode_real_estate_listings'));
            add_shortcode('real-estate-listing', array($this, 'shortcode_real_estate_listing'));
            add_shortcode('real-estate-lead-form', array($this, 'shortcode_lead_form'));
        }

        private function initialize_ajax_actions() {

            add_action('wp_ajax_save_api_photo', array($this->api, 'saveApiPhotoFromPost'));
            add_action('wp_ajax_nopriv_save_api_photo', array($this->api, 'saveApiPhotoFromPost'));
            add_action('wp_ajax_get_listings', array($this->api, 'getListingsAsync'));
            add_action('wp_ajax_nopriv_get_listings', array($this->api, 'getListingsAsync'));
        }

        private function initialize_custom_post_types() {

            add_action('init', array($this, 'create_listing_post_type'), 0);
        }

        private function initialize_options_and_rewrite_rules() {

            // TODO: do we need to initialize rewrite rules every time on every page??? find different hook
            add_action('admin_init', array($this, 'register_listing_options'));
            add_action('admin_menu', array($this, 'wpa_add_menu'));
        }

        /**
         * enqueues main scripts
         * adds local javascript property, accessed as listingsajax.ajax_url
         */
        private function initialize_scripts() {

            wp_enqueue_script('evolvecreate-slideshow', plugin_dir_url(__FILE__) . 'javascript/jquery.evolvecreate.slideshow-2.5.js', array('jquery'), null);
            wp_enqueue_script('listing-js', plugin_dir_url(__FILE__) . 'javascript/listings-1.2.js', array('jquery'), null);

	        wp_localize_script( 'listing-js', 'listingsajax',
                array( 'ajax_url' => admin_url( 'admin-ajax.php' ))
            );
        }

        /**
         * enqueues main styles
         */
        private function initialize_styles() {

            wp_enqueue_style('evolvecreate-css', plugin_dir_url(__FILE__) . 'css/evolvecreate-1.2.css');
            wp_enqueue_style('evolvereate-slideshow-css', plugin_dir_url(__FILE__) . 'css/evolvecreate.slideshow-2.0.css');

        }

        /**
         * register options including default result page and result pages for each city and subdivision
         */
        public function register_listing_options() {

            register_setting('evolve-create-listings', 'listing-result-page');
            register_setting('evolve-create-listings', 'photo-res');

            $keys = $this->api->getCityKeys();
            foreach ($keys as $key) {
                register_setting('evolve-create-listings', $key);
            }

            $keys = $this->api->getSubdivisionKeys();
            foreach ($keys as $key) {
                register_setting('evolve-create-listings', $key);
            }

            $this->initialize_rewrite_rules();
        }

        /**
         * sets up rewrite rules for default result page, city result pages, and subdivision pages
         */
        public function initialize_rewrite_rules() {

            $id = get_option('listing-result-page');
            $post = get_post($id);
            if ($post) {
            $listingURL = $post->post_name . '/';
                add_rewrite_rule($listingURL . '(.*)/$', 'index.php/' . $listingURL . '?rlid=$1', 'top');
            }

            $keys = $this->api->getCityKeys();
            foreach ($keys as $key) {
                $id = get_option($key);
                $post = get_post($id);
                if ($post) {
                    $listingURL = $post->post_name . '/';
                    add_rewrite_rule($listingURL . '(.*)/$', 'index.php/' . $listingURL . '?rlid=$1', 'top');
                }
            }

            $keys = $this->api->getSubdivisionKeys();
            foreach ($keys as $key) {
                $id = get_option($key);
                $post = get_post($id);
                if ($post) {
                    $listingURL = $post->post_name . '/';
                    add_rewrite_rule($listingURL . '(.*)/$', 'index.php/' . $listingURL . '?rlid=$1', 'top');
                }
            }

            global $wp_rewrite;
            $wp_rewrite->flush_rules();

        }


        public function wpa_add_menu() {

            // TODO: change name
             // TODO: figure difference between these two and capabilities, and menu slugs
            add_menu_page('Real Estate Listings',
                'Real Estate Listings',
                'manage_options',
                'real-estate-listings-settings');

            add_submenu_page('real-estate-listings-settings',
                            'Manage Real Estate Listings',
                            'Real Estate Listings',
                            'manage_options',
                            'real-estate-listings-settings',
                            array($this, 'display_settings_menu')
                        );


            add_meta_box( 'real-estate-listings-settings',
                'Real Estate Listings',
                array($this, 'display_real-estate-listings_meta_box'),
                'real-estate-listings', 'normal', 'high'
            );
        }

        public function display_settings_menu() {

            wp_enqueue_style('evolvecreate-css', plugin_dir_url(__FILE__) . 'css/evolvecreate-1.2.css');
            wp_enqueue_script('listing-js', plugin_dir_url(__FILE__) . 'javascript/listings-1.2.js', array('jquery'), null);

            $api = new sparkAPI();
            $spark = new listings($api);

            $spark->init();

            include plugin_dir_path(__FILE__) . 'layout/admin-settings.php';
        }

        public function create_listing_post_type() {

            $labels = array(
                'name'                      => __('Listing'),
                'singular_name'             => __('Listings'),
                'menu_name'                 => __('Listings'),
                'all_items'                 => __('Manage Listings'),
                'view_item'                 => __('View Listing'),
                'add_new_item'              => __('Add New Listing'),
                'add_new'                   => __('Add New'),
                'edit_item'                 => __('Edit Listing'),
                'update_item'               => __('Update Listing'),
                'search_items'              => __('Search Listings'),
                'not_found'                 => __('Not Found'),
                'not_found_in_trash'        => __('Not found in Trash'),
                'featured_image'            => __('Featured Photo'),
                'set_featured_image'        => __('Set Featured Photo'),
                'remove_featured_image'     => __('Remove Featured Photo'),
                'use_featured_image'        => __('Use Featured Photo'),
            );

            $args = array(
                'label'               => __( 'Listings'),
                'description'         => __( 'Home Listings'),
                'labels'              => $labels,
                'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'revisions', 'custom-fields'),
                'hierarchical'        => false,
                'public'              => false, // prevents redirect to listing/$post_name
                'show_ui'             => true,
                'show_in_menu'        => 'listing-settings', // TODO: how do we add it after galleries?
                'show_in_nav_menus'   => false,
                'show_in_admin_bar'   => true,
                'menu_position'       => 100,
                'can_export'          => true,
                'has_archive'         => true,
                'exclude_from_search' => false, // TODO: ist this right?
                'publicly_queryable'  => false, // prevents redirect to listing/$post_name
                'capability_type'     => 'page', // add, edit, remove
            );

            register_post_type('listing', $args);
        }

        public function shortcode_real_estate_listings($attributes) {

            // create new instances for this short code
            $api = new sparkAPI();
            $spark = new listings($api);

            // set attributes
            $defaultAttributes = $this->get_default_shortcode_attributes();
            $spark->attributes = shortcode_atts($defaultAttributes, $attributes);

            if (!$attributes) {
                $attributes = array();
            }

            if (
                (!$attributes['location-key']) &&
                (!$attributes['listing-id'])
            ) {

                // must set $searchOptions from attribute for this specific instance
                foreach ($this->searchOptionNames as $optionName) {
                    if ($spark->attributes[strtolower($optionName)]) {
                        $spark->searchOptions[$optionName] = $attributes[strtolower($optionName)];
                    }
                }

            }

            ob_start();

            if ($_GET['rlid']) {

                $listing = $spark->api->getListingByPostName($_GET['rlid']); // this isn't the rlid, but the pretty name
                include plugin_dir_path(__FILE__) . 'layout/listing.php';

            } else if ($attributes['location-key']) {

                $listing = $spark->api->getListingByPostName($attributes['location-key']); // TODO: this isn't the rlid, but the pretty name
                include plugin_dir_path(__FILE__) . 'layout/listing.php';

            } else if ($attributes['listing-id']) {

                $listing = $spark->api->getListingByListingID($attributes['listing-id']);
                include plugin_dir_path(__FILE__) . 'layout/listing.php';

            } else {

                if ($_POST['options']) {
                    $options = $_POST['options'];
                } else if ($spark->searchOptions) {
                    $options = $spark->searchOptions; // from shortcode attributes
                } else if ($_GET['options']) {
                        $options = $_GET['options'];
                } else {
                    $options = array('bedrooms' => 4, 'baths' => 2);
                }

                $options = $spark->api->translateUserOptions($options);
                $listings = $spark->api->getListings($options);

                include plugin_dir_path(__FILE__) . 'layout/search.php';
            }

            return ob_get_clean();
        }

        private function get_default_shortcode_attributes() {

            $defaultAttributes = array(

                // single listing
                'id'                        => null,
                'mlsid'                     => null,
                'location-key'              => null,
                'listing-id'                => null,

                // layout
                'hide-search'               => false,
                'hide-filters'              => false,
                'hide-listings'             => false,
                'hide-description'          => false,

                'use-neighborhoods-link'    => false,
                'use-cities-link'           => false,
            );
            // add search options to $defaultAttributes
            foreach ($this->searchOptionNames as $optionName) {
                $defaultAttributes[strtolower($optionName)] = null; // use strtolower() because shortcode/html attributes are not camelcase
            }

            return $defaultAttributes;
        }

        public function shortcode_lead_form() {

            ob_start();
            include plugin_dir_path( __FILE__ ) . 'layout/lead-form.php';
            return ob_get_clean();

        }

        public function notify_listing_lead() {

            if ($_POST['contact_email']) {

                $this->api->getListingByPostName($_POST['listing_name']);

                $name    = sanitize_text_field( $_POST['contact_name'] );
                $email   = sanitize_email( $_POST['contact_email'] );
                $subject = 'Lead for ';
                $message = esc_textarea( $_POST['message'] );

                $to = get_option('admin_email');

                $headers = "From: $name <$email>" . "\r\n";


                if ( wp_mail( $to, $subject, $message, $headers ) ) {
                    echo '<div>';
                    echo '<p>Thanks for contacting me, expect a response soon.</p>';
                    echo '</div>';
                } else {
                    echo 'An unexpected error occurred';
                }
            }
        }


    }


