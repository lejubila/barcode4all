<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public static function pages(): array
    {
        return [
            'privacy'       => ['/privacy'],
            'cookie policy' => ['/cookie-policy'],
            'terms'         => ['/terms'],
        ];
    }

    #[DataProvider('pages')]
    public function test_legal_pages_are_reachable(string $path): void
    {
        $this->get($path)->assertOk();
    }

    public function test_privacy_page_is_localized(): void
    {
        $this->withHeaders(['Accept-Language' => 'it'])
            ->get('/privacy')
            ->assertOk()
            ->assertSee('Informativa sulla privacy');

        $this->flushSession();

        $this->withHeaders(['Accept-Language' => 'en'])
            ->get('/privacy')
            ->assertOk()
            ->assertSee('Privacy Policy');
    }

    public function test_home_uses_self_hosted_assets_and_no_third_parties(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('vendor/tailwind/tailwind.js', $html);
        $this->assertStringContainsString('vendor/alpine/alpine.min.js', $html);
        // No third-party origins should be referenced.
        $this->assertStringNotContainsString('cdn.tailwindcss.com', $html);
        $this->assertStringNotContainsString('jsdelivr.net', $html);
    }
}
