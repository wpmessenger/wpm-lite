<?php
/**
 * Plugin Name: Twilio Caller
 * Description: A plugin to enable Twilio calls from WordPress admin area
 * Version: 1.0
 * Author: Your Name
 */

require_once __DIR__ . '/sdk/Twilio/autoload.php';

 
function twilio_caller_menu() {
    add_menu_page('Twilio Caller', 'Twilio Caller', 'manage_options', 'twilio-caller', 'twilio_caller_page', 'dashicons-phone', 6);
}
add_action('admin_menu', 'twilio_caller_menu');

function twilio_caller_settings() {
    register_setting('twilio_caller_options', 'twilio_caller_account_sid');
    register_setting('twilio_caller_options', 'twilio_caller_auth_token');
}
add_action('admin_init', 'twilio_caller_settings');

function twilio_caller_page() {
    ?>
    <div class="wrap">
        <h1>Twilio Caller</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('twilio_caller_options');
                do_settings_sections('twilio_caller_options');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Twilio Account SID</th>
                    <td><input type="text" name="twilio_caller_account_sid" value="<?php echo esc_attr(get_option('twilio_caller_account_sid')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Twilio Auth Token</th>
                    <td><input type="text" name="twilio_caller_auth_token" value="<?php echo esc_attr(get_option('twilio_caller_auth_token')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2>Make a call</h2>
        <div>
            <label for="twilio_number">Twilio Number:</label>
            <input type="text" id="twilio_number" name="twilio_number" />
            <br>
            <label for="personal_number">Personal Number:</label>
            <input type="text" id="personal_number" name="personal_number" />
            <br>
            <label for="other_number">Other Number:</label>
            <input type="text" id="other_number" name="other_number" />
            <br>
            <button id="twilio_call">Call</button>
        </div>
    </div>
    <?php
}

function twilio_caller_admin_scripts($hook) {
    if ($hook != 'toplevel_page_twilio-caller') {
        return;
    }
    wp_enqueue_style('twilio_caller_admin_css', plugin_dir_url(__FILE__) . 'css/admin.css');
    wp_enqueue_script('twilio_caller_admin_js', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), false, true);

    // Pass AJAX URL to JavaScript
    wp_localize_script('twilio_caller_admin_js', 'twilio_caller_vars', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
    ));
}
add_action('admin_enqueue_scripts', 'twilio_caller_admin_scripts');


use Twilio\Rest\Client;

function twilio_call() {
    $account_sid = get_option('twilio_caller_account_sid');
    $auth_token = get_option('twilio_caller_auth_token');
    $twilio_number = isset($_POST['twilio_number']) ? sanitize_text_field($_POST['twilio_number']) : '';
    $personal_number = isset($_POST['personal_number']) ? sanitize_text_field($_POST['personal_number']) : '';
    $other_number = isset($_POST['other_number']) ? sanitize_text_field($_POST['other_number']) : ''; // Corrected this line
	error_log('Other number received in twilio_call: ' . $other_number);

    // Initialize the Twilio client
    $client = new Client($account_sid, $auth_token);

    try {
        $call = $client->calls->create(
            $personal_number,
            $twilio_number,
            [
                'url' => site_url('/twilio-twiml?OtherNumber=' . urlencode($other_number) . '&TwilioNumber=' . urlencode($twilio_number)),
            ]
        );

        echo 'Call initiated. Please wait for the call on your device.';
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
        error_log('Error in twilio_call function: ' . $e->getMessage());

        // Send the error message back to the AJAX response
        $response = array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
        wp_send_json($response); // This will send a JSON response and terminate the script
    }
    wp_die(); // This is required to terminate immediately and return a proper response
}






add_action('wp_ajax_twilio_call', 'twilio_call');

function twilio_twiml_second_phase_content($content) {
    if (is_page_template('twiml-second-phase-template.php')) {
        $other_number = isset($_GET['OtherNumber']) ? $_GET['OtherNumber'] : '';
        $twilio_number = isset($_GET['TwilioNumber']) ? $_GET['TwilioNumber'] : '';

        header('Content-Type: text/xml');
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $content .= "<Response>\n";
        $content .= "    <Dial callerId=\"" . htmlspecialchars($twilio_number, ENT_XML1, 'UTF-8') . "\">\n";
        $content .= "        <Number>" . htmlspecialchars($other_number, ENT_XML1, 'UTF-8') . "</Number>\n";
        $content .= "    </Dial>\n";
        $content .= "</Response>";

        error_log('Generated TwiML for twilio_twiml_second_phase_content: ' . $content);
        return $content;
    }
    return $content;
}

add_filter('the_content', 'twilio_twiml_second_phase_content');


function twilio_twiml_content($content) {
    if (is_page_template('twiml-template.php')) {
        $other_number = isset($_GET['OtherNumber']) ? $_GET['OtherNumber'] : '';
		error_log('Generated TwiML for twilio_twiml_content: OtherNumber: ' . $other_number . ', TwilioNumber: ' . $twilio_number);
        $twilio_number = isset($_GET['TwilioNumber']) ? $_GET['TwilioNumber'] : '';

        header('Content-Type: text/xml');
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $content .= "<Response>\n";
        $content .= "    <Say>Please wait while we connect your call.</Say>\n";
        $content .= "    <Dial action=\"" . htmlspecialchars(site_url('/twilio-twiml-second-phase?OtherNumber=' . urlencode($other_number) . '&TwilioNumber=' . urlencode($twilio_number)), ENT_XML1, 'UTF-8') . "\" callerId=\"" . htmlspecialchars($twilio_number, ENT_XML1, 'UTF-8') . "\">\n";
        $content .= "        <Number>" . htmlspecialchars($personal_number, ENT_XML1, 'UTF-8') . "</Number>\n";
        $content .= "    </Dial>\n";
        $content .= "</Response>";

        error_log('Generated TwiML for twilio_twiml_content: ' . $content);
        return $content;
    }
    return $content;
}

add_filter('the_content', 'twilio_twiml_content');




function wpm_register_twiml_second_phase_template($templates) {
    $templates['twiml-second-phase-template.php'] = 'TwiML Second Phase Template';
    return $templates;
}
add_filter('theme_page_templates', 'wpm_register_twiml_second_phase_template');

function wpm_load_twiml_second_phase_template($template) {
    global $post;
    if ($post && $post->post_type == 'page') {
        $page_template = get_post_meta($post->ID, '_wp_page_template', true);
        if ($page_template == 'twiml-second-phase-template.php') {
            $template = plugin_dir_path(__FILE__) . 'twiml-second-phase-template.php';
        }
    }
    return $template;
}
add_filter('template_include', 'wpm_load_twiml_second_phase_template');


function wpm_register_twiml_template($templates) {
    $templates['twiml-template.php'] = 'TwiML Template';
    return $templates;
}
add_filter('theme_page_templates', 'wpm_register_twiml_template');

function wpm_load_twiml_template($template) {
    global $post;
    if ($post && $post->post_type == 'page') {
        $page_template = get_post_meta($post->ID, '_wp_page_template', true);
        if ($page_template == 'twiml-template.php') {
            $template = plugin_dir_path(__FILE__) . 'twiml-template.php';
        }
    }
    return $template;
}
add_filter('template_include', 'wpm_load_twiml_template');
