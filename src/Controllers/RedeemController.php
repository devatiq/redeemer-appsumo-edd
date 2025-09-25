<?php
namespace REDEASEDD\RedeemerAppSumoEDD\Controllers;

use REDEASEDD\RedeemerAppSumoEDD\Infrastructure\Options;
use REDEASEDD\RedeemerAppSumoEDD\Services\VerifierInterface;
use REDEASEDD\RedeemerAppSumoEDD\Services\EDDService;
use WP_REST_Request;
use WP_REST_Response;

class RedeemController
{
    public function __construct(
        private Options $options,
        private VerifierInterface $verifier,
        private EDDService $edd
    ) {}

    public function register_routes(): void
    {
        register_rest_route('rae/v1', '/redeem', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'redeem' ],
            'permission_callback' => '__return_true',
        ]);
    }

    public function redeem(WP_REST_Request $req): WP_REST_Response
    {
        $opts   = $this->options->get();
        $secret = $this->getBearer($req);

        if ( ! $secret || $secret !== (string) ($opts['webhook_secret'] ?? '') ) {
            return new WP_REST_Response( [ 'error' => 'Unauthorized' ], 401 );
        }

        $email    = sanitize_email( (string) $req->get_param('email') );
        $code     = sanitize_text_field( (string) $req->get_param('code') );
        $price_id = absint( (string) $req->get_param('price_id') );
        $name     = sanitize_text_field( (string) $req->get_param('name') );

        if ( ! is_email($email) ) {
            return new WP_REST_Response( [ 'error' => 'Invalid email' ], 400 );
        }

        $allowed = array_map('intval', (array) ($opts['allowed_price_ids'] ?? []));
        if ( $price_id && ! in_array($price_id, $allowed, true) ) {
            return new WP_REST_Response( [ 'error' => 'Invalid price_id' ], 400 );
        }
        if ( empty($code) ) {
            return new WP_REST_Response( [ 'error' => 'Missing code' ], 400 );
        }

        // Verify the code
        $verify = $this->verifier->verify($code, $price_id ?: null);
        if ( empty($verify['valid']) ) {
            return new WP_REST_Response( [ 'error' => $verify['message'] ?? 'Invalid code' ], 400 );
        }
        $finalPriceId = (int) ($verify['price_id'] ?? $price_id);
        if ( ! $finalPriceId ) {
            return new WP_REST_Response( [ 'error' => 'Could not determine price_id' ], 400 );
        }

        $downloadId = (int) ($opts['download_id'] ?? 0);
        if ( ! $downloadId ) {
            return new WP_REST_Response( [ 'error' => 'Download ID not configured' ], 500 );
        }

        $paymentId = $this->edd->createZeroPayment($email, $name, $downloadId, $finalPriceId);
        if ( ! $paymentId ) {
            return new WP_REST_Response( [ 'error' => 'Failed to create payment' ], 500 );
        }

        // Mark code used
        $this->verifier->markUsed($code, $email, $paymentId);

        $licenses = $this->edd->getPaymentLicenses($paymentId);

        return new WP_REST_Response([
            'ok'          => true,
            'payment_id'  => $paymentId,
            'price_id'    => $finalPriceId,
            'licenses'    => $licenses,
            'message'     => 'Redemption successful.',
        ], 200 );
    }

    private function getBearer(WP_REST_Request $req): ?string
    {
        $header = $req->get_header('authorization') ?: $req->get_header('Authorization');
        if ( ! $header ) return null;
        if ( preg_match('/Bearer\s+(.+)$/i', $header, $m) ) {
            return trim($m[1]);
        }
        return null;
    }
}
