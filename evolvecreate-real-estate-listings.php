<?php
/*
  Plugin Name: evolve create - Real Estate Listings
  Plugin URI: http://www.evolvecreate.com/wordpress-plugins/evolvecreate-real-estate-listings
  Description: Real Estate Search and Listings pull on Spark API
  Version: 1.1
  Author: Grenard Madrigal
  Author URI: http://www.evolvecreate.com
  License: GPLv2+
  Text Domain: evolvecreate-real-estate-listings
*/

if (!defined('ABSPATH')) {
    exit();
}

    require_once plugin_dir_path(__FILE__) . 'sparkAPI.php';
    require_once plugin_dir_path(__FILE__) . 'listings.php';

    $api = new sparkAPI();
    $listings = new listings($api);
    $listings->init();

?>