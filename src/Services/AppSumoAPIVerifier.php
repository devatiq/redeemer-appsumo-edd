<?php
namespace REDEASEDD\RedeemerAppSumoEDD\Services;

class AppSumoAPIVerifier implements VerifierInterface
{
    public function __construct(
        private string $apiBase, // e.g. https://partners.api.appsumo.com
        private string $apiKey
    ) {}

    public function verify(string $code, ?int $requestedPriceId): array
    {
        // PSEUDO: Adjust to AppSumo’s real endpoint and response format
        $resp = wp_remote_get( $this->apiBase.'/v1/codes/'.urlencode($code), [
            'headers' => [ 'Authorization' => 'Bearer '.$this->apiKey ],
            'timeout' => 20,
        ]);
        if ( is_wp_error($resp) ) {
            return ['valid'=>false, 'message'=>'Verification network error'];
        }
        $body = json_decode( wp_remote_retrieve_body($resp), true );
        if ( empty($body['valid']) ) {
            return ['valid'=>false, 'message'=>$body['message'] ?? 'Invalid code'];
        }
        // If AppSumo returns tier or seats, map it to your price_id
        $priceId = $requestedPriceId ?: $this->mapTierToPriceId($body);
        if ( ! $priceId ) return ['valid'=>false, 'message'=>'Tier/price_id could not be determined'];
        return ['valid'=>true, 'price_id'=>$priceId];
    }

    public function markUsed(string $code, string $email, int $paymentId): void
    {
        // Optionally notify AppSumo that the code has been redeemed
        // wp_remote_post(...);
    }

    private function mapTierToPriceId(array $data): ?int
    {
        // Example mapping logic:
        // if ($data['tier'] === 1) return 1;
        // if ($data['tier'] === 2) return 2;
        return null;
    }
}
