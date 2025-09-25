<?php
namespace REDEASEDD\RedeemerAppSumoEDD\Controllers;

use REDEASEDD\RedeemerAppSumoEDD\Infrastructure\Options;
use REDEASEDD\RedeemerAppSumoEDD\Services\VerifierInterface;
use REDEASEDD\RedeemerAppSumoEDD\Services\EDDService;

class AjaxController
{
    public function __construct(
        private Options $options,
        private VerifierInterface $verifier,
        private EDDService $edd
    ) {}

    public function register(): void
    {
        add_action('wp_ajax_rae_redeem',        [$this, 'handle']);
        add_action('wp_ajax_nopriv_rae_redeem', [$this, 'handle']);
    }

    public function handle(): void
    {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'rae-redeem' ) ) {
            wp_send_json( ['error'=>'Invalid nonce'], 403 );
        }

        $opts      = $this->options->get();
        $downloadId= (int) ($opts['download_id'] ?? 0);
        if ( ! $downloadId ) wp_send_json(['error'=>'Download not configured'], 500);

        $email    = sanitize_email( (string) ($_POST['email'] ?? '') );
        $code     = sanitize_text_field( (string) ($_POST['code'] ?? '') );
        $price_id = absint( (string) ($_POST['price_id'] ?? 0) );
        $name     = sanitize_text_field( (string) ($_POST['name'] ?? '') );

        if ( ! is_email($email) ) wp_send_json(['error'=>'Invalid email'], 400);
        if ( empty($code) )       wp_send_json(['error'=>'Missing code'], 400);

        $allowed = array_map('intval', (array) ($opts['allowed_price_ids'] ?? []));
        if ( $price_id && ! in_array($price_id, $allowed, true) ) {
            wp_send_json(['error'=>'Invalid price_id'], 400);
        }

        // Verify using the same service as the REST route (local store or API)
        $verify = $this->verifier->verify($code, $price_id ?: null);
        if ( empty($verify['valid']) ) {
            wp_send_json(['error'=> $verify['message'] ?? 'Invalid code'], 400);
        }

        $finalPriceId = (int) ($verify['price_id'] ?? $price_id);
        if ( ! $finalPriceId ) wp_send_json(['error'=>'Could not determine price_id'], 400);

        $paymentId = $this->edd->createZeroPayment($email, $name, $downloadId, $finalPriceId);
        if ( ! $paymentId ) wp_send_json(['error'=>'Failed to create payment'], 500);

        $this->verifier->markUsed($code, $email, $paymentId);

        $licenses = $this->edd->getPaymentLicenses($paymentId);

        wp_send_json([
            'ok'         => true,
            'payment_id' => $paymentId,
            'price_id'   => $finalPriceId,
            'licenses'   => $licenses,
            'message'    => 'Redemption successful.'
        ]);
    }
}
