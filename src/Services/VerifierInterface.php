<?php
namespace REDEASEDD\RedeemerAppSumoEDD\Services;

interface VerifierInterface
{
    /** @return array{valid:bool, price_id?:int, message?:string} */
    public function verify(string $code, ?int $requestedPriceId): array;

    public function markUsed(string $code, string $email, int $paymentId): void;
}
