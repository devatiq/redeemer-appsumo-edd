<?php
namespace REDEASEDD\RedeemerAppSumoEDD\Infrastructure;

class Options
{
    private string $key   = 'rae_options';
    private string $group = 'rae_group';

    public function key(): string { return $this->key; }
    public function group(): string { return $this->group; }

    public function defaults(): array
    {
        return [
            'webhook_secret'     => '',
            'download_id'        => 2067,
            'allowed_price_ids'  => [1, 2],
            'infer_tier'         => 1,
            'codes_store'        => '',
        ];
    }

    public function get(): array
    {
        $opts = get_option($this->key, []);
        return wp_parse_args($opts, $this->defaults());
    }

    /** Parse the codes_store text area into an array map. */
    public function loadCodes(): array
    {
        $raw = (string) ($this->get()['codes_store'] ?? '');
        if (! $raw) return [];
        $lines = array_filter(array_map('trim', explode("\n", $raw)));
        $store = [];
        foreach ($lines as $line) {
            // CODE|PRICE_ID|USED|USED_BY|PAYMENT_ID
            $parts = array_map('trim', explode('|', $line));
            if ( count($parts) >= 2 ) {
                $store[$parts[0]] = [
                    'price_id'   => absint($parts[1]),
                    'used'       => isset($parts[2]) ? absint($parts[2]) : 0,
                    'used_by'    => $parts[3] ?? '',
                    'payment_id' => isset($parts[4]) ? absint($parts[4]) : 0,
                ];
            }
        }
        return $store;
    }

    public function saveCodes(array $store): void
    {
        $opts = $this->get();
        $opts['codes_store'] = implode("\n", array_map(function ($code, $row) {
            return $code . '|' . (int) $row['price_id'] . '|' . (int) ($row['used'] ?? 0) . '|' . ($row['used_by'] ?? '') . '|' . (int) ($row['payment_id'] ?? 0);
        }, array_keys($store), array_values($store)));
        update_option($this->key, $opts, false);
    }
}
