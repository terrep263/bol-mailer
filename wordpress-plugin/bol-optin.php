<?php
/**
 * Plugin Name: BOL Opt-in Form
 * Description: Book of Lies email opt-in shortcode — posts to mailer.thebookoflies.online via Brevo
 * Version: 2.0.0
 * Author: The Book of Lies
 */

if (!defined('ABSPATH')) exit;

// ── Admin settings ──────────────────────────────────────────────────────────
add_action('admin_menu', function () {
    add_options_page('BOL Mailer', 'BOL Mailer', 'manage_options', 'bol-mailer', 'bol_settings_page');
});

add_action('admin_init', function () {
    register_setting('bol_mailer_settings', 'bol_mailer_url', ['sanitize_callback' => 'sanitize_text_field']);
});

function bol_settings_page() { ?>
    <div class="wrap">
        <h1>BOL Mailer Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('bol_mailer_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="bol_mailer_url">Mailer Base URL</label></th>
                    <td>
                        <input type="url" id="bol_mailer_url" name="bol_mailer_url"
                            value="<?php echo esc_attr(get_option('bol_mailer_url', 'https://mailer.thebookoflies.online')); ?>"
                            class="regular-text" />
                        <p class="description">e.g. https://mailer.thebookoflies.online (no trailing slash)</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php }

// ── Enqueue styles once ──────────────────────────────────────────────────────
add_action('wp_enqueue_scripts', function () {
    wp_add_inline_style('divi-style', '
        .bol-optin-wrap { text-align:center; }
        .bol-optin-row { display:flex; max-width:460px; margin:0 auto 10px auto; gap:0; }
        .bol-optin-row input[type=email] {
            flex:1; padding:14px 16px; font-size:15px;
            background:#ffffff; border:none; color:#111;
            font-family:Georgia,serif; outline:none;
            -webkit-appearance:none;
        }
        .bol-optin-row input[type=email]::placeholder { color:#999; }
        .bol-optin-btn {
            padding:14px 20px; background:#B22234; color:#fff;
            font-family:"Barlow Condensed",Arial Narrow,sans-serif;
            font-size:13px; font-weight:900; letter-spacing:.12em;
            text-transform:uppercase; border:none; cursor:pointer;
            white-space:nowrap; transition:background .2s;
        }
        .bol-optin-btn:hover { background:#9a1e2d; }
        .bol-optin-btn:disabled { background:#666; cursor:not-allowed; }
        .bol-optin-fine {
            font-family:"Barlow Condensed",Arial Narrow,sans-serif;
            font-size:11px; color:rgba(255,255,255,.35);
            letter-spacing:.08em; margin:0;
        }
        .bol-optin-success {
            font-family:"Barlow Condensed",Arial Narrow,sans-serif;
            font-size:16px; font-weight:700; color:#c9a84c;
            letter-spacing:.1em; margin:12px 0 0; display:none;
        }
        .bol-optin-error {
            font-size:13px; color:#ff6b6b;
            margin:8px 0 0; display:none;
        }
        @media(max-width:520px){
            .bol-optin-row { flex-direction:column; }
            .bol-optin-btn { width:100%; }
        }
    ');
});

// ── Shortcode: [bol_optin sequence="bol-faith-prelaunch"] ───────────────────
add_shortcode('bol_optin', 'bol_render_optin_form');

function bol_render_optin_form($atts) {
    $atts = shortcode_atts([
        'sequence' => 'bol-faith-prelaunch',
        'button'   => 'Send Me Chapter 1',
    ], $atts, 'bol_optin');

    $mailer_url  = esc_url(rtrim(get_option('bol_mailer_url', 'https://mailer.thebookoflies.online'), '/'));
    $sequence_id = esc_attr($atts['sequence']);
    $btn_label   = esc_html($atts['button']);
    $uid         = 'bol' . substr(md5(uniqid()), 0, 8);

    ob_start(); ?>
    <div class="bol-optin-wrap">
        <div class="bol-optin-row" id="<?php echo $uid; ?>-row">
            <input type="email"
                   id="<?php echo $uid; ?>-email"
                   placeholder="Your email address"
                   autocomplete="email" />
            <button class="bol-optin-btn"
                    id="<?php echo $uid; ?>-btn"><?php echo $btn_label; ?></button>
        </div>
        <p class="bol-optin-fine" id="<?php echo $uid; ?>-fine">No spam. Unsubscribe anytime.</p>
        <p class="bol-optin-success" id="<?php echo $uid; ?>-ok">Your chapter is on its way. Check your inbox.</p>
        <p class="bol-optin-error"  id="<?php echo $uid; ?>-err"></p>
    </div>
    <script>
    (function(){
        var uid   = '<?php echo $uid; ?>';
        var url   = '<?php echo $mailer_url; ?>/api/subscribe';
        var seq   = '<?php echo $sequence_id; ?>';
        var btn   = document.getElementById(uid+'-btn');
        var inp   = document.getElementById(uid+'-email');
        var row   = document.getElementById(uid+'-row');
        var fine  = document.getElementById(uid+'-fine');
        var ok    = document.getElementById(uid+'-ok');
        var err   = document.getElementById(uid+'-err');
        function send(){
            var email = inp.value.trim();
            err.style.display = 'none';
            if(!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){
                err.textContent = 'Please enter a valid email address.';
                err.style.display = 'block'; return;
            }
            btn.disabled = true; btn.textContent = 'Sending...';
            fetch(url, {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body:JSON.stringify({email:email, firstName:'Friend', sequenceId:seq})
            })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if(d.success){
                    row.style.display  = 'none';
                    fine.style.display = 'none';
                    ok.style.display   = 'block';
                } else {
                    err.textContent    = d.error || 'Something went wrong. Try again.';
                    err.style.display  = 'block';
                    btn.disabled       = false;
                    btn.textContent    = '<?php echo $btn_label; ?>';
                }
            })
            .catch(function(){
                err.textContent   = 'Connection error. Try again.';
                err.style.display = 'block';
                btn.disabled      = false;
                btn.textContent   = '<?php echo $btn_label; ?>';
            });
        }
        btn.addEventListener('click', send);
        inp.addEventListener('keydown', function(e){ if(e.key==='Enter') send(); });
    })();
    </script>
    <?php
    return ob_get_clean();
}
