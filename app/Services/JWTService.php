<?php

namespace App\Services;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

class JWTService
{
    private $config;

    public function __construct()
    {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText(env('JWT_SECRET')) // Ensure JWT_SECRET is in .env
        );
    }

    public function createToken($user)
    {
        return $this->config->builder()
            ->issuedBy(config('app.url')) // Issuer
            ->permittedFor(config('app.url')) // Audience
            ->identifiedBy(bin2hex(random_bytes(16)), true) // Token ID
            ->issuedAt(new \DateTimeImmutable()) // Issued at
            ->canOnlyBeUsedAfter(new \DateTimeImmutable()) // Can be used immediately
            ->expiresAt((new \DateTimeImmutable())->modify('+1 hour')) // Expiration
            ->withClaim('uid', $user->id) // Add custom claim
            ->getToken($this->config->signer(), $this->config->signingKey()); // Returns token
    }

    public function validateToken(string $token): bool
    {
        $token = $this->config->parser()->parse($token);
        $constraint = new SignedWith($this->config->signer(), $this->config->signingKey());

        return $this->config->validator()->validate($token, $constraint);
    }



    // public function validateToken(string $token): bool
    // {
    //     $token = $this->config->parser()->parse($token);

    //     return $this->config->validator()->validate($token, ...$this->config->validationConstraints());
    // }

    // public function validateToken($token)
    // {
    //     $constraint = new SignedWith($this->config->signer(), $this->config->signingKey());

    //     try {
    //         return $this->config->validator()->validate($token, $constraint);
    //     } catch (\Exception $e) {
    //         /* \Log::error('JWT Validation failed: ' . $e->getMessage()); */
    //         return false;
    //     }
    // }
}
