<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MarketplaceApiTest extends TestCase
{
    use RefreshDatabase;

    public function testMarketplaceUpdateCheck()
    {
        $response = $this->postJson('/api/core/update', [
            'core' => md5('test-core-hash'),
            'plugins' => serialize([
                'Kregel.Root' => '1.0.0',
                'WebVPF.Robots' => '2.0.0',
                'Winter.Blocks' => '1.0.0',
                'Winter.SSO' => '1.0.0',
            ]),
            'themes' => serialize([
                'Winter.CmsTheme' => '1.0.0',
                'Winter.AdminTheme' => '1.0.0',
            ]),
            'build' => null,
            'force' => true,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'core',
                     'plugins',
                     'themes',
                     'build',
                     'force',
                     'status'
                 ]);
    }
}
