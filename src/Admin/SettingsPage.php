<?php
namespace REDEASEDD\RedeemerAppSumoEDD\Admin;

use REDEASEDD\RedeemerAppSumoEDD\Infrastructure\Options;

class SettingsPage
{
    public static function menu(): void
    {
        add_options_page(
            __('Redeemer for AppSumo + EDD', 'redeemer-appsumo-edd'),
            __('AppSumo Redeemer', 'redeemer-appsumo-edd'),
            'manage_options',
            'redeemer-appsumo-edd',
            [self::class, 'render']
        );
    }

    public static function register(): void
    {
        $opts = new Options();

        register_setting($opts->group(), $opts->key(), [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize'],
            'default' => $opts->defaults(),
        ]);

        add_settings_section('redeasedd_main', __('General', 'redeemer-appsumo-edd'), '__return_false', 'redeemer-appsumo-edd');

        add_settings_field('webhook_secret', __('Webhook/REST Secret', 'redeemer-appsumo-edd'), function () use ($opts) {
            $v = $opts->get()['webhook_secret'] ?? '';
            printf('<input type="text" class="regular-text" name="%s[webhook_secret]" value="%s" />', esc_attr($opts->key()), esc_attr($v));
            echo '<p class="description">Provide in requests as <code>Authorization: Bearer &lt;secret&gt;</code>.</p>';
        }, 'redeemer-appsumo-edd', 'redeasedd_main');

        add_settings_field('download_id', __('EDD Download ID', 'redeemer-appsumo-edd'), function () use ($opts) {
            $v = (int) ($opts->get()['download_id'] ?? 0);
            printf('<input type="number" min="1" name="%s[download_id]" value="%d" />', esc_attr($opts->key()), $v);
        }, 'redeemer-appsumo-edd', 'redeasedd_main');

        add_settings_field('allowed_price_ids', __('Allowed Price IDs (CSV)', 'redeemer-appsumo-edd'), function () use ($opts) {
            $v = esc_attr( implode(',', array_map('intval', (array) ($opts->get()['allowed_price_ids'] ?? []))) );
            printf('<input type="text" class="regular-text" name="%s[allowed_price_ids]" value="%s" />', esc_attr($opts->key()), $v);
            echo '<p class="description">Example: <code>1,2</code></p>';
        }, 'redeemer-appsumo-edd', 'redeasedd_main');

        add_settings_field('infer_tier', __('Infer Tier from Code Prefix', 'redeemer-appsumo-edd'), function () use ($opts) {
            $v = ! empty($opts->get()['infer_tier']);
            printf('<label><input type="checkbox" name="%s[infer_tier]" value="1" %s /> %s</label>',
                esc_attr($opts->key()),
                checked(true, $v, false),
                esc_html__('If code starts with AS-1- or AS-2-, use 1 or 2 as price_id.', 'redeemer-appsumo-edd')
            );
        }, 'redeemer-appsumo-edd', 'redeasedd_main');

        add_settings_field('codes_store', __('Seed Codes (one per line: CODE|PRICE_ID)', 'redeemer-appsumo-edd'), function () use ($opts) {
            $v = (string) ($opts->get()['codes_store'] ?? '');
            printf('<textarea name="%s[codes_store]" class="large-text code" rows="8">%s</textarea>', esc_attr($opts->key()), esc_textarea($v));
            echo '<p class="description">Example: <code>AS-1-ABCDE12345|1</code></p>';
        }, 'redeemer-appsumo-edd', 'redeasedd_main');
    }

    public static function render(): void
    {
        $opts = new Options();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Redeemer for AppSumo + EDD', 'redeemer-appsumo-edd'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($opts->group());
                do_settings_sections('redeemer-appsumo-edd');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function sanitize($input)
    {
        $out = [];
        $out['webhook_secret'] = sanitize_text_field( $input['webhook_secret'] ?? '' );
        $out['download_id']    = absint( $input['download_id'] ?? 0 );
        $csv                   = sanitize_text_field( $input['allowed_price_ids'] ?? '' );
        $out['allowed_price_ids'] = array_values(array_filter(array_map('absint', explode(',', $csv))));
        $out['infer_tier']     = ! empty($input['infer_tier']) ? 1 : 0;

        // Parse seed codes (optional local verification store)
        $lines = array_filter(array_map('trim', explode("\n", (string) ($input['codes_store'] ?? ''))));
        $store = [];
        foreach ($lines as $line) {
            // CODE|PRICE_ID
            $parts = array_map('trim', explode('|', $line));
            if ( count($parts) >= 2 ) {
                $store[$parts[0]] = [
                    'price_id'   => absint($parts[1]),
                    'used'       => 0,
                    'used_by'    => '',
                    'payment_id' => 0
                ];
            }
        }
        $out['codes_store'] = implode("\n", array_map(function ($code, $row) {
            return $code . '|' . (int) $row['price_id'] . '|' . (int) ($row['used'] ?? 0) . '|' . ($row['used_by'] ?? '') . '|' . (int) ($row['payment_id'] ?? 0);
        }, array_keys($store), array_values($store)));

        return $out;
    }
}
