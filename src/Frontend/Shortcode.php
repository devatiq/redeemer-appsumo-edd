<?php
namespace REDEASEDD\RedeemerAppSumoEDD\Frontend;

use REDEASEDD\RedeemerAppSumoEDD\Infrastructure\Options;

class Shortcode
{
    const SLUG = 'redeasedd'; // used for handles/actions/nonces

    public static function register(): void
    {
        add_shortcode('appsumo_redeem_form', [self::class, 'render']);
        add_action('wp_enqueue_scripts', [self::class, 'assets']);
    }

    public static function assets(): void
    {
        // Use your bootstrap constant
        $base = defined('REDEASEDD_URL') ? REDEASEDD_URL : plugin_dir_url(__FILE__) . '../../';

        wp_register_style(self::SLUG . '-frontend', $base . 'assets/css/frontend.css', [], '1.0.0');
        wp_register_script(self::SLUG . '-frontend', $base . 'assets/js/frontend.js', [], '1.0.0', true);

        wp_localize_script(self::SLUG . '-frontend', 'REDEASEDD_AJAX', [
            'url'    => admin_url('admin-ajax.php'),
            'nonce'  => wp_create_nonce(self::SLUG . '_redeem'),
            'action' => self::SLUG . '_redeem',
        ]);
    }

    public static function render($atts = []): string
    {
        $o = new Options();
        $opts = $o->get();
        $allowed = array_map('intval', (array) ($opts['allowed_price_ids'] ?? []));

        wp_enqueue_style(self::SLUG . '-frontend');
        wp_enqueue_script(self::SLUG . '-frontend');

        ob_start(); ?>
        <div class="redeasedd-wrap" role="region" aria-label="Code Redemption">
          <div class="redeasedd-card" role="form" aria-labelledby="redeasedd-form-title">
            <h3 id="redeasedd-form-title" class="redeasedd-header">Redeem Your Code</h3>
            <p class="redeasedd-sub">Enter your purchase email and code. We’ll create your license automatically.</p>

            <form class="redeasedd-redeem-form" novalidate>
              <div class="redeasedd-grid">
                <div class="redeasedd-row">
                  <label class="redeasedd-label" for="redeasedd-email">Email</label>
                  <input id="redeasedd-email" name="email" type="email" class="redeasedd-input" autocomplete="email" required placeholder="you@example.com" />
                  <div class="redeasedd-hint">Your license will be attached to this email.</div>
                  <div class="redeasedd-error" data-err="email" aria-live="polite"></div>
                </div>

                <div class="redeasedd-row">
                  <label class="redeasedd-label" for="redeasedd-name">Full name (optional)</label>
                  <input id="redeasedd-name" name="name" type="text" class="redeasedd-input" autocomplete="name" placeholder="Your Name" />
                </div>

                <div class="redeasedd-row">
                  <label class="redeasedd-label" for="redeasedd-code">Code</label>
                  <input id="redeasedd-code" name="code" type="text" class="redeasedd-input" required placeholder="AS-1-XXXXXXX" inputmode="latin-prose" />
                  <div class="redeasedd-hint">Tip: Prefix like <code>AS-1-</code> or <code>AS-2-</code> helps match your tier.</div>
                  <div class="redeasedd-error" data-err="code" aria-live="polite"></div>
                </div>

                <div class="redeasedd-row">
                  <label class="redeasedd-label" for="redeasedd-tier">Tier (Price ID)</label>
                  <select id="redeasedd-tier" name="price_id" class="redeasedd-select" required>
                    <?php foreach($allowed as $pid): ?>
                      <option value="<?php echo esc_attr($pid); ?>">Tier <?php echo esc_html($pid); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <div class="redeasedd-error" data-err="price_id" aria-live="polite"></div>
                </div>

                <div class="redeasedd-actions">
                  <button type="submit" class="redeasedd-btn">Redeem</button>
                </div>
              </div>

              <div class="redeasedd-output" aria-live="polite" aria-atomic="true"></div>
            </form>
          </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
