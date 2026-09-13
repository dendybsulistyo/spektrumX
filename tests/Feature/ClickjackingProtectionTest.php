<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ClickjackingProtectionTest extends TestCase
{
    public function test_root_and_login_responses_include_security_headers(): void
    {
        foreach (['/', '/login'] as $uri) {
            $response = $this->get($uri)
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('Content-Security-Policy');

            $policy = (string) $response->headers->get('Content-Security-Policy');

            $this->assertStringContainsString("default-src 'self'", $policy);
            $this->assertStringContainsString("object-src 'none'", $policy);
            $this->assertStringContainsString("frame-ancestors 'self'", $policy);
            $this->assertStringContainsString("form-action 'self'", $policy);
        }
    }

    public function test_php_version_disclosure_header_is_removed(): void
    {
        Route::get('/security-header-probe', fn () => response('OK')->header('X-Powered-By', 'PHP/8.3.12'));

        $this->get('/security-header-probe')
            ->assertOk()
            ->assertHeaderMissing('X-Powered-By');
    }
}
