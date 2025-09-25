<?php
namespace REDEASEDD\RedeemerAppSumoEDD\Services;

use REDEASEDD\RedeemerAppSumoEDD\Infrastructure\Options;

class LocalStoreVerifier implements VerifierInterface
{
    public function __construct(private Options $options) {}

    public function verify(string $code, ?int $requestedPriceId): array
    {
        $opts  = $this->options->get();
        $store = $this->options->loadCodes();

        // Infer price_id by code prefix if enabled
        if ( empty($requestedPriceId) && ! empty($opts['infer_tier']) ) {
            if ( str_starts_with($code, 'AS-1-') ) $requestedPriceId = 1;
            if ( str_starts_with($code, 'AS-2-') ) $requestedPriceId = 2;
        }

        if ( ! $requestedPriceId ) {
            return ['valid' => false, 'message' => 'Missing price_id'];
        }

        if ( empty($store[$code]) ) {
            return ['valid' => false, 'message' => 'Code not found'];
        }
        $row = $store[$code];

        if ( ! empty($row['used']) ) {
            return ['valid' => false, 'message' => 'Code already used'];
        }
        if ( (int) $row['price_id'] !== (int) $requestedPriceId ) {
            return ['valid' => false, 'message' => 'Tier mismatch'];
        }

        return ['valid' => true, 'price_id' => (int) $requestedPriceId];
    }

    public function markUsed(string $code, string $email, int $paymentId): void
    {
        $store = $this->options->loadCodes();
        if ( isset($store[$code]) ) {
            $store[$code]['used']       = 1;
            $store[$code]['used_by']    = $email;
            $store[$code]['payment_id'] = $paymentId;
            $this->options->saveCodes($store);
        }
    }
}
