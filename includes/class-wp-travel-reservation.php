<?php
/**
 * Core plugin functionality for WP Travel Reservation Form.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Travel_Reservation {
    private static $instance;

    /**
     * Get singleton instance.
     */
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_post_types']);
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post', [$this, 'save_post_meta']);
        add_shortcode('travel_itinerary_overview', [$this, 'render_itinerary_shortcode']);
    }

    /**
     * Register custom post types for activities, clients, and itineraries.
     */
    public function register_post_types() {
        register_post_type('wptravel_activity', [
            'labels' => [
                'name' => __('Activities', 'wp-travel-reservation'),
                'singular_name' => __('Activity', 'wp-travel-reservation'),
                'add_new_item' => __('Add New Activity', 'wp-travel-reservation'),
                'edit_item' => __('Edit Activity', 'wp-travel-reservation'),
            ],
            'public' => false,
            'show_ui' => true,
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-location-alt',
        ]);

        register_post_type('wptravel_client', [
            'labels' => [
                'name' => __('Clients', 'wp-travel-reservation'),
                'singular_name' => __('Client', 'wp-travel-reservation'),
                'add_new_item' => __('Add New Client', 'wp-travel-reservation'),
                'edit_item' => __('Edit Client', 'wp-travel-reservation'),
            ],
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-groups',
        ]);

        register_post_type('wptravel_itinerary', [
            'labels' => [
                'name' => __('Itineraries', 'wp-travel-reservation'),
                'singular_name' => __('Itinerary', 'wp-travel-reservation'),
                'add_new_item' => __('Add New Itinerary', 'wp-travel-reservation'),
                'edit_item' => __('Edit Itinerary', 'wp-travel-reservation'),
            ],
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-calendar-alt',
        ]);
    }

    /**
     * Register meta boxes for post types.
     */
    public function register_meta_boxes() {
        add_meta_box('wptravel_activity_details', __('Activity Details', 'wp-travel-reservation'), [$this, 'render_activity_meta'], 'wptravel_activity', 'normal', 'high');
        add_meta_box('wptravel_client_details', __('Client Details', 'wp-travel-reservation'), [$this, 'render_client_meta'], 'wptravel_client', 'normal', 'high');
        add_meta_box('wptravel_itinerary_schedule', __('Schedule & Costs', 'wp-travel-reservation'), [$this, 'render_itinerary_meta'], 'wptravel_itinerary', 'normal', 'high');
    }

    /**
     * Render activity meta fields.
     */
    public function render_activity_meta($post) {
        wp_nonce_field('wptravel_activity_meta', 'wptravel_activity_meta_nonce');
        $day = get_post_meta($post->ID, '_wptravel_activity_day', true);
        $cost = get_post_meta($post->ID, '_wptravel_activity_cost', true);
        ?>
        <p>
            <label for="wptravel_activity_day"><strong><?php _e('Day', 'wp-travel-reservation'); ?></strong></label>
            <input type="number" id="wptravel_activity_day" name="wptravel_activity_day" value="<?php echo esc_attr($day); ?>" min="1" step="1" class="widefat" />
        </p>
        <p>
            <label for="wptravel_activity_cost"><strong><?php _e('Cost (e.g. 200 for 200€)', 'wp-travel-reservation'); ?></strong></label>
            <input type="number" id="wptravel_activity_cost" name="wptravel_activity_cost" value="<?php echo esc_attr($cost); ?>" min="0" step="0.01" class="widefat" />
        </p>
        <p class="description"><?php _e('Use the content editor for the activity description.', 'wp-travel-reservation'); ?></p>
        <?php
    }

    /**
     * Render client meta fields.
     */
    public function render_client_meta($post) {
        wp_nonce_field('wptravel_client_meta', 'wptravel_client_meta_nonce');
        $email = get_post_meta($post->ID, '_wptravel_client_email', true);
        $phone = get_post_meta($post->ID, '_wptravel_client_phone', true);
        ?>
        <p>
            <label for="wptravel_client_email"><strong><?php _e('Email', 'wp-travel-reservation'); ?></strong></label>
            <input type="email" id="wptravel_client_email" name="wptravel_client_email" value="<?php echo esc_attr($email); ?>" class="widefat" />
        </p>
        <p>
            <label for="wptravel_client_phone"><strong><?php _e('Phone', 'wp-travel-reservation'); ?></strong></label>
            <input type="text" id="wptravel_client_phone" name="wptravel_client_phone" value="<?php echo esc_attr($phone); ?>" class="widefat" />
        </p>
        <?php
    }

    /**
     * Render itinerary meta fields for selecting client and activities.
     */
    public function render_itinerary_meta($post) {
        wp_nonce_field('wptravel_itinerary_meta', 'wptravel_itinerary_meta_nonce');
        $selected_client = get_post_meta($post->ID, '_wptravel_itinerary_client', true);
        $schedule = get_post_meta($post->ID, '_wptravel_itinerary_schedule', true);
        $activities = $this->get_activities();
        $clients = $this->get_clients();

        if (!is_array($schedule)) {
            $schedule = [];
        }
        ?>
        <p>
            <label for="wptravel_itinerary_client"><strong><?php _e('Client', 'wp-travel-reservation'); ?></strong></label>
            <select id="wptravel_itinerary_client" name="wptravel_itinerary_client" class="widefat">
                <option value=""><?php _e('Select a client', 'wp-travel-reservation'); ?></option>
                <?php foreach ($clients as $client) : ?>
                    <option value="<?php echo esc_attr($client->ID); ?>" <?php selected($selected_client, $client->ID); ?>><?php echo esc_html($client->post_title); ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <table class="widefat" id="wptravel-schedule-table">
            <thead>
                <tr>
                    <th><?php _e('Day', 'wp-travel-reservation'); ?></th>
                    <th><?php _e('Activity', 'wp-travel-reservation'); ?></th>
                    <th><?php _e('Cost', 'wp-travel-reservation'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schedule)) : ?>
                    <?php $this->render_schedule_row([], $activities); ?>
                <?php else : ?>
                    <?php foreach ($schedule as $row) : ?>
                        <?php $this->render_schedule_row($row, $activities); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <p>
            <button type="button" class="button" id="wptravel-add-row"><?php _e('Add Activity', 'wp-travel-reservation'); ?></button>
        </p>
        <p><strong><?php _e('Total Cost:', 'wp-travel-reservation'); ?></strong> <span id="wptravel-total-cost" data-total="<?php echo esc_attr($this->calculate_schedule_total($schedule)); ?>"><?php echo esc_html($this->format_price($this->calculate_schedule_total($schedule))); ?></span></p>

        <script>
            (function($){
                const activities = <?php echo wp_json_encode($this->get_activity_cost_map($activities)); ?>;
                function updateTotals() {
                    let total = 0;
                    $('#wptravel-schedule-table tbody tr').each(function(){
                        const select = $(this).find('select[name="wptravel_schedule_activity[]"]');
                        const activityId = select.val();
                        const costCell = $(this).find('.wptravel-schedule-cost');
                        const cost = activities[activityId] ? parseFloat(activities[activityId]) : 0;
                        costCell.text(cost ? cost.toFixed(2) : '0.00');
                        if (cost) {
                            total += cost;
                        }
                    });
                    $('#wptravel-total-cost').text(new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(total));
                }
                $('#wptravel-add-row').on('click', function(){
                    const newRow = $('#wptravel-schedule-table tbody tr:last').clone();
                    newRow.find('input, select').val('');
                    $('#wptravel-schedule-table tbody').append(newRow);
                    updateTotals();
                });
                $('#wptravel-schedule-table').on('change', 'select[name="wptravel_schedule_activity[]"]', updateTotals);
                updateTotals();
            })(jQuery);
        </script>
        <?php
    }

    /**
     * Output a single schedule row.
     */
    private function render_schedule_row($row, $activities) {
        $day = isset($row['day']) ? absint($row['day']) : '';
        $activity_id = isset($row['activity']) ? absint($row['activity']) : '';
        ?>
        <tr>
            <td><input type="number" name="wptravel_schedule_day[]" value="<?php echo esc_attr($day); ?>" min="1" step="1" /></td>
            <td>
                <select name="wptravel_schedule_activity[]">
                    <option value=""><?php _e('Select activity', 'wp-travel-reservation'); ?></option>
                    <?php foreach ($activities as $activity) : ?>
                        <option value="<?php echo esc_attr($activity->ID); ?>" <?php selected($activity_id, $activity->ID); ?>><?php echo esc_html($activity->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td class="wptravel-schedule-cost"></td>
        </tr>
        <?php
    }

    /**
     * Save meta data for custom post types.
     */
    public function save_post_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['wptravel_activity_meta_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wptravel_activity_meta_nonce'])), 'wptravel_activity_meta')) {
            $this->save_activity_meta($post_id);
        }

        if (isset($_POST['wptravel_client_meta_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wptravel_client_meta_nonce'])), 'wptravel_client_meta')) {
            $this->save_client_meta($post_id);
        }

        if (isset($_POST['wptravel_itinerary_meta_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wptravel_itinerary_meta_nonce'])), 'wptravel_itinerary_meta')) {
            $this->save_itinerary_meta($post_id);
        }
    }

    private function save_activity_meta($post_id) {
        if ('wptravel_activity' !== get_post_type($post_id)) {
            return;
        }

        $day = isset($_POST['wptravel_activity_day']) ? absint(wp_unslash($_POST['wptravel_activity_day'])) : '';
        $cost = isset($_POST['wptravel_activity_cost']) ? floatval(wp_unslash($_POST['wptravel_activity_cost'])) : '';

        update_post_meta($post_id, '_wptravel_activity_day', $day);
        update_post_meta($post_id, '_wptravel_activity_cost', $cost);
    }

    private function save_client_meta($post_id) {
        if ('wptravel_client' !== get_post_type($post_id)) {
            return;
        }

        $email = isset($_POST['wptravel_client_email']) ? sanitize_email(wp_unslash($_POST['wptravel_client_email'])) : '';
        $phone = isset($_POST['wptravel_client_phone']) ? sanitize_text_field(wp_unslash($_POST['wptravel_client_phone'])) : '';

        update_post_meta($post_id, '_wptravel_client_email', $email);
        update_post_meta($post_id, '_wptravel_client_phone', $phone);
    }

    private function save_itinerary_meta($post_id) {
        if ('wptravel_itinerary' !== get_post_type($post_id)) {
            return;
        }

        $client = isset($_POST['wptravel_itinerary_client']) ? absint(wp_unslash($_POST['wptravel_itinerary_client'])) : '';
        $days = isset($_POST['wptravel_schedule_day']) ? array_map('absint', (array) wp_unslash($_POST['wptravel_schedule_day'])) : [];
        $activities = isset($_POST['wptravel_schedule_activity']) ? array_map('absint', (array) wp_unslash($_POST['wptravel_schedule_activity'])) : [];

        update_post_meta($post_id, '_wptravel_itinerary_client', $client);

        $schedule = [];
        $count = max(count($days), count($activities));
        for ($i = 0; $i < $count; $i++) {
            if (empty($days[$i]) && empty($activities[$i])) {
                continue;
            }
            $schedule[] = [
                'day' => isset($days[$i]) ? absint($days[$i]) : '',
                'activity' => isset($activities[$i]) ? absint($activities[$i]) : '',
            ];
        }

        update_post_meta($post_id, '_wptravel_itinerary_schedule', $schedule);
        update_post_meta($post_id, '_wptravel_itinerary_total', $this->calculate_schedule_total($schedule));
    }

    /**
     * Retrieve activities.
     */
    private function get_activities() {
        return get_posts([
            'post_type' => 'wptravel_activity',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
    }

    /**
     * Retrieve clients.
     */
    private function get_clients() {
        return get_posts([
            'post_type' => 'wptravel_client',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
    }

    /**
     * Build a map of activity ID to cost.
     */
    private function get_activity_cost_map($activities) {
        $map = [];
        foreach ($activities as $activity) {
            $map[$activity->ID] = (float) get_post_meta($activity->ID, '_wptravel_activity_cost', true);
        }
        return $map;
    }

    private function calculate_schedule_total($schedule) {
        $total = 0;
        foreach ($schedule as $row) {
            if (empty($row['activity'])) {
                continue;
            }
            $cost = get_post_meta(absint($row['activity']), '_wptravel_activity_cost', true);
            if ($cost) {
                $total += (float) $cost;
            }
        }
        return $total;
    }

    private function format_price($price) {
        return number_format((float) $price, 2);
    }

    /**
     * Render itinerary on the frontend via shortcode.
     */
    public function render_itinerary_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);
        $post_id = absint($atts['id']);
        if (!$post_id) {
            return '';
        }

        $post = get_post($post_id);
        if (!$post || 'wptravel_itinerary' !== $post->post_type) {
            return '';
        }

        $client_id = get_post_meta($post_id, '_wptravel_itinerary_client', true);
        $client_name = $client_id ? get_the_title($client_id) : '';
        $schedule = get_post_meta($post_id, '_wptravel_itinerary_schedule', true);
        if (!is_array($schedule)) {
            $schedule = [];
        }
        ob_start();
        ?>
        <div class="wptravel-itinerary-overview">
            <h3><?php echo esc_html($post->post_title); ?></h3>
            <?php if ($client_name) : ?>
                <p><strong><?php _e('Client:', 'wp-travel-reservation'); ?></strong> <?php echo esc_html($client_name); ?></p>
            <?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <th><?php _e('Day', 'wp-travel-reservation'); ?></th>
                        <th><?php _e('Activity', 'wp-travel-reservation'); ?></th>
                        <th><?php _e('Cost', 'wp-travel-reservation'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedule as $row) : ?>
                        <?php $activity_title = !empty($row['activity']) ? get_the_title($row['activity']) : ''; ?>
                        <?php $cost = !empty($row['activity']) ? get_post_meta($row['activity'], '_wptravel_activity_cost', true) : 0; ?>
                        <tr>
                            <td><?php echo esc_html(isset($row['day']) ? $row['day'] : ''); ?></td>
                            <td><?php echo esc_html($activity_title); ?></td>
                            <td><?php echo esc_html($this->format_price($cost)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" style="text-align:right;"><?php _e('Total', 'wp-travel-reservation'); ?></th>
                        <th><?php echo esc_html($this->format_price($this->calculate_schedule_total($schedule))); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}
