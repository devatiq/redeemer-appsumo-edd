<?php
namespace REDEASEDD\RedeemerAppSumoEDD\Services;

class EDDService
{
    public function createZeroPayment(string $email, string $buyerName, int $downloadId, int $priceId): int
    {
        if ( ! function_exists('edd_insert_payment') ) return 0;

        // Ensure WP user exists
        $user = get_user_by('email', $email);
        if ( ! $user ) {
            $user_id = wp_insert_user([
                'user_login'   => $email,
                'user_email'   => $email,
                'user_pass'    => wp_generate_password(18),
                'display_name' => $buyerName ?: $email,
                'role'         => 'subscriber',
            ]);
            if ( is_wp_error($user_id) ) return 0;
            $user = get_user_by('id', $user_id);
        }
        $user_id = $user->ID;

        $purchase_key = strtolower( md5( $email . time() . wp_rand() ) );
        $currency     = function_exists('edd_get_currency') ? edd_get_currency() : 'USD';

        $cart_details = [
            [
                'name'        => get_the_title($downloadId),
                'id'          => $downloadId,
                'item_number' => [
                    'id'      => $downloadId,
                    'options' => [ 'price_id' => $priceId ],
                ],
                'price'      => 0,
                'quantity'   => 1,
                'subtotal'   => 0,
                'tax'        => 0,
                'discount'   => 0,
                'item_price' => 0,
            ],
        ];

        $payment_data = [
            'price'        => 0,
            'date'         => current_time('mysql'),
            'user_email'   => $email,
            'purchase_key' => $purchase_key,
            'currency'     => $currency,
            'downloads'    => [
                [ 'id' => $downloadId, 'options' => [ 'price_id' => $priceId ] ]
            ],
            'user_info'    => [
                'id'         => $user_id,
                'email'      => $email,
                'first_name' => $buyerName,
            ],
            'cart_details' => $cart_details,
            'status'       => 'publish', // completed
        ];

        $payment_id = edd_insert_payment( $payment_data );
        return (int) $payment_id;
    }

    /** @return array<int, array{license_key:string, download_id:int, price_id:int, status:string, activation_limit:int|string, expires:string}> */
    public function getPaymentLicenses(int $paymentId): array
    {
        $out = [];
        if ( function_exists('edd_software_licensing') && method_exists(edd_software_licensing(), 'get_licenses_of_payment') ) {
            $licenses = edd_software_licensing()->get_licenses_of_payment( $paymentId );
            if ( $licenses ) {
                foreach ($licenses as $lic) {
                    $out[] = [
                        'license_key'      => $lic->key,
                        'download_id'      => (int) $lic->download_id,
                        'price_id'         => (int) $lic->price_id,
                        'status'           => $lic->status,
                        'activation_limit' => $lic->activation_limit,
                        'expires'          => $lic->expiration,
                    ];
                }
            }
        }
        return $out;
    }
}
