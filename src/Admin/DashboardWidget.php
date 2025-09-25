<?php
namespace REDEASEDD\RedeemerAppSumoEDD\Admin;

use REDEASEDD\RedeemerAppSumoEDD\Infrastructure\Options;

class DashboardWidget
{
    public static function register(): void
    {
        add_action('wp_dashboard_setup', [self::class, 'addWidget']);
    }

    public static function addWidget(): void
    {
        wp_add_dashboard_widget(
            'redeasedd_widget',
            __('Redeemer for AppSumo + EDD', 'redeemer-appsumo-edd'),
            [self::class, 'render']
        );
    }

    public static function render(): void
    {
        $opts = (new Options())->get();

        // Helpers
        $mask = function (string $s): string {
            if ($s === '') return '—';
            $len = strlen($s);
            if ($len <= 6) return str_repeat('•', $len);
            return substr($s, 0, 3) . str_repeat('•', max(0, $len - 6)) . substr($s, -3);
        };

        $allowed = implode(', ', array_map('intval', (array) ($opts['allowed_price_ids'] ?? [])));

        // Count seeded codes & unused codes (from textarea store)
        $codesRaw = (string) ($opts['codes_store'] ?? '');
        $lines    = array_filter(array_map('trim', explode("\n", $codesRaw)));
        $totalCodes = count($lines);
        $unused = 0;
        foreach ($lines as $line) {
            // CODE|PRICE_ID|USED|USED_BY|PAYMENT_ID
            $parts = array_map('trim', explode('|', $line));
            $used  = isset($parts[2]) ? (int) $parts[2] : 0;
            if ($used === 0) $unused++;
        }

        ?>
        <div style="font: 14px/1.5 system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;">
            <table class="widefat striped" style="margin-top:8px;">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Webhook/REST Secret', 'redeemer-appsumo-edd'); ?></th>
                        <td><code><?php echo esc_html( $mask( (string) ($opts['webhook_secret'] ?? '') ) ); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('EDD Download ID', 'redeemer-appsumo-edd'); ?></th>
                        <td><?php echo (int) ($opts['download_id'] ?? 0); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Allowed Price IDs', 'redeemer-appsumo-edd'); ?></th>
                        <td><?php echo esc_html($allowed ?: '—'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Infer Tier from Code Prefix', 'redeemer-appsumo-edd'); ?></th>
                        <td><?php echo !empty($opts['infer_tier']) ? 'Enabled' : 'Disabled'; ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Seed Codes', 'redeemer-appsumo-edd'); ?></th>
                        <td><?php printf(
                            esc_html__('%d total (%d unused)', 'redeemer-appsumo-edd'),
                            (int) $totalCodes,
                            (int) $unused
                        ); ?></td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top:10px;">
                <a class="button button-primary" href="<?php echo esc_url( admin_url('options-general.php?page=redeemer-appsumo-edd') ); ?>">
                    <?php esc_html_e('Edit Settings', 'redeemer-appsumo-edd'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
