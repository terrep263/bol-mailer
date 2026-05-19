<?php
/**
 * Plugin Name: BOL Opt-in Form
 * Description: Book of Lies email opt-in — native Brevo form with AJAX submission and brand styling
 * Version: 3.0.0
 * Author: The Book of Lies
 */

if (!defined('ABSPATH')) exit;

// ── Enqueue Brevo stylesheet once per page ────────────────────────────────────
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'brevo-sib-styles',
        'https://sibforms.com/forms/end-form/build/sib-styles.css',
        [],
        null
    );
});

// ── Shortcode: [bol_optin] ────────────────────────────────────────────────────
add_shortcode('bol_optin', 'bol_render_optin_form');

function bol_render_optin_form($atts) {
    $atts = shortcode_atts([
        'button' => 'SEND ME CHAPTER 1',
    ], $atts, 'bol_optin');

    $uid       = 'bol' . substr(md5(uniqid()), 0, 8);
    $btn_label = esc_html($atts['button']);

    ob_start(); ?>

<style>
/* ── BOL brand overrides for Brevo form ───────────────────────────── */
#<?php echo $uid; ?> {
    background: transparent !important;
    border: none !important;
    max-width: 460px;
    margin: 0 auto;
    text-align: center;
}
#<?php echo $uid; ?> .sib-form-block,
#<?php echo $uid; ?> .form__entry {
    padding: 0 !important;
}
#<?php echo $uid; ?> .form__label-row {
    display: flex !important;
    gap: 0;
    margin-bottom: 10px;
}
#<?php echo $uid; ?> input.input {
    flex: 1;
    padding: 14px 16px !important;
    font-size: 15px !important;
    font-family: Georgia, serif !important;
    background: #ffffff !important;
    border: none !important;
    border-radius: 0 !important;
    color: #111 !important;
    outline: none !important;
    -webkit-appearance: none;
    width: 100% !important;
    box-sizing: border-box;
}
#<?php echo $uid; ?> input.input::placeholder {
    color: #999 !important;
    font-family: Georgia, serif !important;
    font-size: 14px !important;
    text-transform: none !important;
}
#<?php echo $uid; ?> .sib-form-block__button {
    display: block;
    width: 100%;
    padding: 14px 20px !important;
    background: #B22234 !important;
    color: #fff !important;
    font-family: 'Barlow Condensed', Arial Narrow, sans-serif !important;
    font-size: 13px !important;
    font-weight: 900 !important;
    letter-spacing: .15em !important;
    text-transform: uppercase !important;
    border: none !important;
    border-radius: 0 !important;
    cursor: pointer !important;
    transition: background .2s;
    margin-top: 4px;
}
#<?php echo $uid; ?> .sib-form-block__button:hover { background: #9a1e2d !important; }
#<?php echo $uid; ?> .entry__error {
    font-family: 'Barlow Condensed', Arial Narrow, sans-serif !important;
    font-size: 12px !important;
    color: #ff6b6b !important;
    background: transparent !important;
    border: none !important;
    padding: 2px 0 !important;
}
#<?php echo $uid; ?> #success-message-<?php echo $uid; ?> {
    font-family: 'Barlow Condensed', Arial Narrow, sans-serif;
    font-size: 16px;
    font-weight: 700;
    color: #c9a84c;
    letter-spacing: .1em;
    background: transparent !important;
    border: none !important;
    margin-top: 12px;
}
#<?php echo $uid; ?> #error-message-<?php echo $uid; ?> {
    font-family: 'Barlow Condensed', Arial Narrow, sans-serif;
    font-size: 13px;
    color: #ff6b6b;
    background: transparent !important;
    border: none !important;
    margin-top: 8px;
}
.bol-optin-fine-<?php echo $uid; ?> {
    font-family: 'Barlow Condensed', Arial Narrow, sans-serif;
    font-size: 11px;
    color: rgba(255,255,255,.35);
    letter-spacing: .08em;
    margin: 10px 0 0;
    text-align: center;
}
@media(max-width:520px){
    #<?php echo $uid; ?> .form__label-row { flex-direction: column !important; }
}
</style>

<div id="<?php echo $uid; ?>">
  <div id="error-message-<?php echo $uid; ?>" style="display:none;">Your subscription could not be saved. Please try again.</div>
  <div id="success-message-<?php echo $uid; ?>" style="display:none;">Your chapter is on its way. Check your inbox.</div>

  <form id="sib-form-<?php echo $uid; ?>" method="POST"
    action="https://23fbce9e.sibforms.com/serve/MUIFAN_C1_UfZGgPzhzc-krRIMbgKWOt-1TpeC9K12Clu2n7q-4iruCpzGyx-JjkUGZoygnTOLvym2Ot5oAOVxGEhOLNjUm9gnLzTk5YMDes1B2E3J5pCMLHnnrre_ZI868X95WxL9DtHBaA1rHd4Pbsh80lHcKNk_R9QTOcLCaOFTGZ12W2Jyxvr6crz9yRcvN0EX4nyQBRW28A"
    data-type="subscription">

    <div class="sib-form-block">
      <div class="form__entry entry_block">
        <div class="form__label-row form__label-row--horizontal">
          <div class="entry__field">
            <input class="input" type="text" id="EMAIL-<?php echo $uid; ?>" name="EMAIL"
              autocomplete="email" placeholder="Your email address"
              data-required="true" required />
          </div>
        </div>
        <label class="entry__error entry__error--primary"></label>
      </div>
    </div>

    <div class="sib-form-block">
      <div class="form__entry entry_block">
        <div class="form__label-row form__label-row--horizontal">
          <div class="entry__field">
            <input class="input" type="text" id="FIRSTNAME-<?php echo $uid; ?>" name="FIRSTNAME"
              maxlength="200" autocomplete="given-name" placeholder="Your first name"
              data-required="true" required />
          </div>
        </div>
        <label class="entry__error entry__error--primary"></label>
      </div>
    </div>

    <div class="sib-form-block">
      <button class="sib-form-block__button" type="submit"><?php echo $btn_label; ?></button>
    </div>

    <input type="text" name="email_address_check" value="" style="display:none;">
    <input type="hidden" name="locale" value="en">
  </form>

  <p class="bol-optin-fine-<?php echo $uid; ?>">No spam. Unsubscribe anytime.</p>
</div>

<script>
(function(){
    var uid     = '<?php echo $uid; ?>';
    var form    = document.getElementById('sib-form-' + uid);
    var errBox  = document.getElementById('error-message-' + uid);
    var okBox   = document.getElementById('success-message-' + uid);

    if (!form) return;

    form.addEventListener('submit', function(e){
        e.preventDefault();
        errBox.style.display = 'none';
        okBox.style.display  = 'none';

        var btn    = form.querySelector('button[type=submit]');
        var data   = new FormData(form);
        var params = new URLSearchParams();
        data.forEach(function(v, k){ params.append(k, v); });

        btn.disabled    = true;
        btn.textContent = 'SENDING...';

        fetch(form.action, {
            method:  'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body:    params.toString()
        })
        .then(function(r){
            // Brevo returns 200 on success, redirects on failure
            if(r.ok || r.status === 200){
                form.style.display  = 'none';
                okBox.style.display = 'block';
            } else {
                throw new Error('status ' + r.status);
            }
        })
        .catch(function(){
            // Brevo form POSTs cross-origin and may be blocked by CORS on fetch.
            // Fall back: submit the form natively so Brevo handles it.
            form.removeEventListener('submit', arguments.callee);
            form.submit();
        });
    });
})();
</script>

<?php
    return ob_get_clean();
}
