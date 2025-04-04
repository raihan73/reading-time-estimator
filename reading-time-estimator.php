<?php
/**
 * Plugin Name: Reading Time Estimator
 * Description: Displays an estimated reading time for blog posts.
 * Version: 1.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RTE_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Add settings menu
function rte_add_admin_menu() {
    add_options_page(
        'Reading Time Estimator', 
        'Reading Time Estimator', 
        'manage_options', 
        'reading-time-estimator', 
        'rte_settings_page'
    );
}
add_action('admin_menu', 'rte_add_admin_menu');

// Register settings
function rte_register_settings() {
    register_setting('rte_settings_group', 'rte_reading_speed', ['default' => 200]);
    register_setting('rte_settings_group', 'rte_custom_text', ['default' => 'Estimated reading time: {time} mins']);
}
add_action('admin_init', 'rte_register_settings');

// Admin settings page
function rte_settings_page() {
    ?>
    <div class="wrap">
        <h1>Reading Time Estimator Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('rte_settings_group'); ?>
            <?php do_settings_sections('rte_settings_group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Reading Speed (words per minute)</th>
                    <td><input type="number" name="rte_reading_speed" value="<?php echo esc_attr(get_option('rte_reading_speed', 200)); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row">Custom Text</th>
                    <td><input type="text" name="rte_custom_text" value="<?php echo esc_attr(get_option('rte_custom_text', 'Estimated reading time: {time} mins')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Calculate reading time
function rte_calculate_reading_time($content) {
    $word_count = str_word_count(strip_tags($content));
    $reading_speed = get_option('rte_reading_speed', 200);
    $reading_time = ceil($word_count / $reading_speed);
    $custom_text = get_option('rte_custom_text', 'Estimated reading time: {time} mins');
    return str_replace('{time}', $reading_time, $custom_text);
}

// Shortcode to display reading time
function rte_shortcode() {
    global $post;
    if (!$post) return '';
    return '<p>' . rte_calculate_reading_time($post->post_content) . '</p>';
}
add_shortcode('reading_time', 'rte_shortcode');

// Register Elementor Widget
function rte_register_elementor_widget($widgets_manager) {
    require_once(__DIR__ . '/rte-elementor-widget.php');
    $widgets_manager->register(new \RTE_Elementor_Widget());
}
add_action('elementor/widgets/register', 'rte_register_elementor_widget');

// Elementor Widget Class
class RTE_Elementor_Widget extends \Elementor\Widget_Base {
    public function get_name() {
        return 'reading_time_widget';
    }

    public function get_title() {
        return __('Reading Time', 'rte');
    }

    public function get_icon() {
        return 'eicon-clock';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Settings', 'rte'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'custom_text',
            [
                'label' => __('Custom Text', 'rte'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => get_option('rte_custom_text', 'Estimated reading time: {time} mins'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'rte'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => __('Text Color', 'rte'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rte-reading-time' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        global $post;
        if (!$post) return;
        $reading_time = rte_calculate_reading_time($post->post_content);
        echo '<p class="rte-reading-time">' . esc_html($reading_time) . '</p>';
    }
}