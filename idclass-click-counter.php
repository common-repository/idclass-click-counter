<?php
/*
Plugin Name: IDClass Click Counter
Description: A plugin to generate class or id, track frontend clicks on elements, and display them on the settings page.
Version: 1.0
Author: Tahsinur Tamim
Author URI: https://www.linkedin.com/in/tahsinur-tamim-95707b170/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Create the menu item in the WordPress admin dashboard
function click_counter_menu() {
    add_menu_page(
        'Click Counter Settings', // Menu Title
        'idclasss Click Counter', // Page Title
        'manage_options',
        'click-counter-settings',
        'click_counter_settings_page',
        'dashicons-chart-bar',
        20
    );
}
add_action('admin_menu', 'click_counter_menu');

// Register and display settings page
function click_counter_settings_page() {
    ?>
    <div class="wrap click-counter-settings">
        <h1>Click Counter Settings</h1>
        
        <h2>Generated IDs/Classes and Clicks</h2>
        <?php
        $elements = get_option('click_counter_elements', []);
        if (!empty($elements)) {
            echo '<ul class="click-counter-list">';
            foreach ($elements as $element) {
                echo '<li>';
                echo '<span class="element-name">' . esc_html($element) . '</span>';
                echo '<span class="click-count">Clicks: ' . esc_html(get_option('click_counter_' . $element, 0)) . '</span>';
                echo '<div class="button-group">';
                echo ' <a class="delete-button" href="?page=click-counter-settings&delete_element=' . urlencode($element) . '&_wpnonce=' . esc_attr(wp_create_nonce('delete_element_nonce')) . '">Delete</a>';
                echo '</div></li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No elements have been added yet.</p>';
        }
        ?>

        <h2>Add New Class/ID</h2>
        <form method="post" class="add-element-form">
            <?php wp_nonce_field('add_element_nonce'); ?>
            <input type="text" name="new_element" placeholder="Enter new class or id" required>
            <select name="type">
                <option value="class">Class</option>
                <option value="id">ID</option>
            </select>
            <input type="submit" name="add_element" value="Add Element" class="button button-primary">
        </form>
    </div>
    <?php
}

// Add new class or id element with nonce verification
function click_counter_add_element() {
    if (isset($_POST['add_element'])) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'add_element_nonce')) {
            wp_die('Nonce verification failed');
        }

        // Check if 'new_element' and 'type' are set
        if (isset($_POST['new_element']) && isset($_POST['type'])) {
            // Unslash and sanitize the inputs
            $new_element = sanitize_text_field(wp_unslash($_POST['new_element']));
            $type = sanitize_text_field(wp_unslash($_POST['type']));

            if (!empty($new_element)) {
                $elements = get_option('click_counter_elements', []);
                $element_key = ($type === 'id' ? '#' : '.') . $new_element;
                if (!in_array($element_key, $elements)) {
                    $elements[] = $element_key;
                    update_option('click_counter_elements', $elements);
                    update_option('click_counter_' . $element_key, 0);  // Initialize click count to 0
                }
            }
        }
    }
}
add_action('admin_init', 'click_counter_add_element');

// Delete element with nonce verification
function click_counter_delete_element() {
    if (isset($_GET['delete_element']) && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'delete_element_nonce')) {
        $element_to_delete = urldecode(sanitize_text_field(wp_unslash($_GET['delete_element'])));
        $elements = get_option('click_counter_elements', []);
        if (($key = array_search($element_to_delete, $elements)) !== false) {
            unset($elements[$key]);
            update_option('click_counter_elements', $elements);
            delete_option('click_counter_' . $element_to_delete);
        }
    } elseif (isset($_GET['delete_element'])) {
        wp_die('Nonce verification failed');
    }
}
add_action('admin_init', 'click_counter_delete_element');

// Register plugin settings
function click_counter_register_settings() {
    register_setting('click_counter_settings_group', 'click_counter_elements');
}
add_action('admin_init', 'click_counter_register_settings');

// Enqueue admin styles
function click_counter_enqueue_admin_styles() {
    wp_enqueue_style('click-counter-style', plugin_dir_url(__FILE__) . 'assets/css/click-counter.css', [], '1.0');
}
add_action('admin_enqueue_scripts', 'click_counter_enqueue_admin_styles');

// Enqueue front-end scripts and styles
function click_counter_enqueue_scripts() {
    // Check if we are on a post or page
    if (is_single() || is_page()) {
        $elements = get_option('click_counter_elements', []);
        if (!empty($elements)) {
            wp_enqueue_script('click-counter-script', plugin_dir_url(__FILE__) . 'assets/js/click-counter.js', ['jquery'], '1.0', true);
            wp_localize_script('click-counter-script', 'clickCounterAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'elements' => $elements,
                'nonce'    => wp_create_nonce('click_counter_nonce'),
            ]);
        }
    }
}
add_action('wp_enqueue_scripts', 'click_counter_enqueue_scripts');


// Handle Ajax request to increment click counter
function click_counter_increment() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'click_counter_nonce')) {
        wp_send_json_error('Nonce verification failed');
    }

    // Check if 'element' is set
    if (isset($_POST['element'])) {
        $element = sanitize_text_field(wp_unslash($_POST['element']));
        $clicks = get_option('click_counter_' . $element, 0);
        $clicks++;
        update_option('click_counter_' . $element, $clicks);
        wp_send_json_success(['clicks' => $clicks]);
    } else {
        wp_send_json_error('Element not specified');
    }
}
add_action('wp_ajax_click_counter_increment', 'click_counter_increment');
add_action('wp_ajax_nopriv_click_counter_increment', 'click_counter_increment');
