<?php

/**
 * Plugin Name: My Custom Form
 * Description: Form UI panel for sending and receiving form data
 * Author: Chinmoy Biswas
 * Version: 1.0
 * Author URI: https://chinmoybiswas.com/
 * Text Domain: my-custom-form
 */
session_start();
// Enqueue Bootstrap styles and scripts
function enqueue_bootstrap()
{
    wp_enqueue_style('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery'), '', true);
}
add_action('wp_enqueue_scripts', 'enqueue_bootstrap');

// Form HTML
function custom_form_html()
{
    ob_start();
?>
    <form id="custom-form" class="container mt-4" method="post">
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" class="form-control" name="name" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" class="form-control" name="email" required>
        </div>

        <div class="form-group">
            <label for="message">Message:</label>
            <textarea class="form-control" name="message" rows="4" required></textarea>
        </div>

        <button type="submit" name="submit" class="btn btn-primary">Submit</button>
    </form>
<?php
    return ob_get_clean();
}

// Shortcode for displaying the form
function custom_form_shortcode()
{
    return custom_form_html();
}
add_shortcode('custom_form', 'custom_form_shortcode');

// Handle form submission and save data to the database
function handle_form_submission() {
    global $wpdb;

    if (isset($_POST['submit'])) {
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $message = sanitize_textarea_field($_POST['message']);
        $submission_time = current_time('mysql');

        $table_name = $wpdb->prefix . 'form_submissions';

        $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'email' => $email,
                'message' => $message,
                'submission_time' => $submission_time,
            )
        );

        // Save submitted name and email in session variables
        $_SESSION['submitted_name'] = $name;
        $_SESSION['submitted_email'] = $email;

        // Redirect to the "Thank You" page
        wp_redirect(get_permalink(get_page_by_title('Thank You')->ID));
        exit();
    }
}
add_action('init', 'handle_form_submission');



// Create a custom database table for form submissions
function create_submission_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'form_submissions';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        message text NOT NULL,
        submission_time datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_submission_table');

// Shortcode for displaying thank you message with submitted data
function thank_you_shortcode() {
    $name = isset($_SESSION['submitted_name']) ? $_SESSION['submitted_name'] : '';
    $email = isset($_SESSION['submitted_email']) ? $_SESSION['submitted_email'] : '';

    ob_start();
    ?>
    <div class="thank-you-message">
        <h2>Thank You for Your Submission</h2>
        <?php
        if (!empty($name) && !empty($email)) {
            echo '<p>Name: ' . esc_html($name) . '</p>';
            echo '<p>Email: ' . esc_html($email) . '</p>';
        } else {
            echo '<p>No submission data found.</p>';
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('thank_you_message', 'thank_you_shortcode');


// Add submenu page for form submissions
function add_form_submissions_submenu() {
    add_submenu_page(
        'options-general.php', // Parent menu slug
        'Form Submissions',     // Page title
        'Form Submissions',     // Menu title
        'manage_options',       // Capability required
        'form-submissions',     // Menu slug
        'display_form_submissions_page' // Callback function to display content
    );
}
add_action('admin_menu', 'add_form_submissions_submenu');


// Display form submissions page in the admin panel
function display_form_submissions_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'form_submissions';

    $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submission_time DESC");

    echo '<div class="wrap">';
    echo '<h2>Form Submissions</h2>';
    if (!empty($submissions)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Name</th><th>Email</th><th>Message</th><th>Submission Time</th></tr></thead>';
        echo '<tbody>';
        foreach ($submissions as $submission) {
            echo '<tr>';
            echo '<td>' . esc_html($submission->name) . '</td>';
            echo '<td>' . esc_html($submission->email) . '</td>';
            echo '<td>' . esc_html($submission->message) . '</td>';
            echo '<td>' . esc_html($submission->submission_time) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No form submissions found.</p>';
    }
    echo '</div>';
}

