<?php
/**
 * Plugin Name: BOL Opt-in Form
 * Description: Book of Lies email opt-in form with Listmonk integration
 * Version: 1.0.0
 * Author: The Book of Lies
 */

if (!defined('ABSPATH')) exit;

// Register admin settings
add_action('admin_menu', function () {
    add_options_page('BOL Mailer', 'BOL Mailer', 'manage_options', 'bol-mailer', 'bol_settings_page');
});

add_action('admin_init', function () {
    register_setting('bol_mailer_settings', 'bol_mailer_url', ['sanitize_callback' => 'sanitize_text_field']);
});

function bol_settings_page() {
    ?>
    <div class="wrap">
        <h1>BOL Mailer Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('bol_mailer_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="bol_mailer_url">Mailer URL</label></th>
                    <td>
                        <input type="url" id="bol_mailer_url" name="bol_mailer_url"
                            value="<?php echo esc_attr(get_option('bol_mailer_url', 'https://mailer.thebookoflies.online')); ?>"
                            class="regular-text" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register shortcode
add_shortcode('bol_optin', 'bol_render_optin_form');

function bol_render_optin_form() {
    $mailer_url = esc_url(get_option('bol_mailer_url', 'https://mailer.thebookoflies.online'));
    $form_id = 'bol-optin-' . uniqid();
    ob_start();
    ?>
    <div id="<?php echo $form_id; ?>" style="background:#0d0d0d;padding:32px;border-radius:8px;max-width:480px;margin:0 auto;font-family:Georgia,serif;">
        <h3 style="color:#c9a84c;margin:0 0 8px;font-size:1.4rem;">Get Chapter 1 Free</h3>
        <p style="color:#ccc;margin:0 0 24px;font-size:0.95rem;">Enter your details below and we'll send it straight to your inbox.</p>

        <div class="bol-form-wrap">
            <input type="text" id="bol-firstname-<?php echo $form_id; ?>" placeholder="First Name"
                style="width:100%;padding:12px 16px;margin-bottom:12px;background:#1a1a1a;border:1px solid #333;color:#fff;border-radius:4px;font-size:1rem;box-sizing:border-box;" />

            <input type="email" id="bol-email-<?php echo $form_id; ?>" placeholder="Email Address"
                style="width:100%;padding:12px 16px;margin-bottom:16px;background:#1a1a1a;border:1px solid #333;color:#fff;border-radius:4px;font-size:1rem;box-sizing:border-box;" />

            <button id="bol-submit-<?php echo $form_id; ?>"
                style="width:100%;padding:14px;background:#c9a84c;color:#0d0d0d;border:none;border-radius:4px;font-size:1rem;font-weight:bold;cursor:pointer;letter-spacing:0.05em;">
                SEND ME CHAPTER 1
            </button>
        </div>

        <div id="bol-success-<?php echo $form_id; ?>" style="display:none;color:#c9a84c;margin-top:16px;font-size:1rem;">
            Your chapter is on its way. Check your inbox.
        </div>
        <div id="bol-error-<?php echo $form_id; ?>" style="display:none;color:#e74c3c;margin-top:16px;font-size:0.9rem;">
            Something went wrong. Please try again.
        </div>
    </div>

    <script>
    (function() {
        var formId = '<?php echo $form_id; ?>';
        var mailerUrl = '<?php echo $mailer_url; ?>';

        document.getElementById('bol-submit-' + formId).addEventListener('click', function() {
            var firstName = document.getElementById('bol-firstname-' + formId).value.trim();
            var email = document.getElementById('bol-email-' + formId).value.trim();
            var btn = this;

            document.getElementById('bol-success-' + formId).style.display = 'none';
            document.getElementById('bol-error-' + formId).style.display = 'none';

            if (!firstName || !email) {
                document.getElementById('bol-error-' + formId).textContent = 'Please fill in all fields.';
                document.getElementById('bol-error-' + formId).style.display = 'block';
                return;
            }

            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                document.getElementById('bol-error-' + formId).textContent = 'Please enter a valid email address.';
                document.getElementById('bol-error-' + formId).style.display = 'block';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'SENDING...';

            fetch(mailerUrl + '/api/subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email, firstName: firstName, sequenceId: 'bol-faith-prelaunch' })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('bol-success-' + formId).style.display = 'block';
                    document.querySelector('#' + formId + ' .bol-form-wrap').style.display = 'none';
                } else {
                    document.getElementById('bol-error-' + formId).textContent = 'Something went wrong. Please try again.';
                    document.getElementById('bol-error-' + formId).style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = 'SEND ME CHAPTER 1';
                }
            })
            .catch(function() {
                document.getElementById('bol-error-' + formId).textContent = 'Something went wrong. Please try again.';
                document.getElementById('bol-error-' + formId).style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'SEND ME CHAPTER 1';
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}
