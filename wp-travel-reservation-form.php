<?php
/**
 * Plugin Name: WP Travel Reservation Form
 * Description: Manage travel activities, clients, and itineraries with cost overviews.
 * Version: 1.0.0
 * Author: OpenAI Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WP_TRAVEL_RESERVATION_VERSION', '1.0.0');
define('WP_TRAVEL_RESERVATION_PATH', plugin_dir_path(__FILE__));
define('WP_TRAVEL_RESERVATION_URL', plugin_dir_url(__FILE__));

require_once WP_TRAVEL_RESERVATION_PATH . 'includes/class-wp-travel-reservation.php';

WP_Travel_Reservation::get_instance();
