<?php
namespace REDEASEDD\RedeemerAppSumoEDD\Frontend;

use REDEASEDD\RedeemerAppSumoEDD\Infrastructure\Options;

class Shortcode
{
    public static function register(): void
    {
        add_shortcode('appsumo_redeem_form', [self::class, 'render']);
        add_action('wp_enqueue_scripts', [self::class, 'assets']);
    }

    public static function assets(): void
    {
        // Minimal inline script to handle AJAX submission
        wp_register_script('rae-form', '', [], null, true);
        $ajax = [
            'url'   => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rae-redeem'),
        ];
        wp_add_inline_script('rae-form', 'window.RAE_AJAX='.wp_json_encode($ajax).';'.
        <<<JS
document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('form.rae-redeem-form').forEach(function(f){
    f.addEventListener('submit', async function(e){
      e.preventDefault();
      const fd = new FormData(f);
      const out = f.querySelector('.rae-output');
      out.textContent='Processing...';
      try{
        const resp = await fetch(RAE_AJAX.url, {
          method:'POST',
          credentials:'same-origin',
          headers:{},
          body: new URLSearchParams({
            action:'rae_redeem',
            _wpnonce: RAE_AJAX.nonce,
            email: fd.get('email')||'',
            name: fd.get('name')||'',
            code: fd.get('code')||'',
            price_id: fd.get('price_id')||''
          })
        });
        const json = await resp.json();
        if(json && json.ok){
          out.innerHTML = '✅ Success!<br><code>'+JSON.stringify(json.licenses, null, 2)+'</code>';
        }else{
          out.textContent = '❌ ' + (json && json.error ? json.error : 'Unknown error');
        }
      }catch(err){
        out.textContent = '❌ Network error';
      }
    });
  });
});
JS
        );
    }

    public static function render($atts = []): string
    {
        $o = new Options();
        $opts = $o->get();
        $allowed = array_map('intval', (array) ($opts['allowed_price_ids'] ?? []));

        ob_start(); wp_enqueue_script('rae-form'); ?>
        <form class="rae-redeem-form" method="post">
          <div>
            <label>Email<br><input type="email" name="email" required></label>
          </div>
          <div>
            <label>Name (optional)<br><input type="text" name="name"></label>
          </div>
          <div>
            <label>AppSumo Code<br><input type="text" name="code" required></label>
          </div>
          <div>
            <label>Tier (Price ID)</label><br>
            <select name="price_id" required>
              <?php foreach($allowed as $pid): ?>
                <option value="<?php echo esc_attr($pid); ?>"><?php echo esc_html($pid); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="margin-top:8px;">
            <button type="submit">Redeem</button>
          </div>
          <div class="rae-output" style="margin-top:8px;white-space:pre-wrap;"></div>
        </form>
        <?php
        return (string) ob_get_clean();
    }
}
