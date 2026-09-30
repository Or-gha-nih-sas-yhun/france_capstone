<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAppTest extends TestCase
{
    use RefreshDatabase;

    private const APP_UA = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/128.0 Mobile Safari/537.36 MerasUserApp/1.0';

    /** @test */
    public function the_app_can_hand_over_its_push_token_after_the_page_has_loaded()
    {
        $this->postJson('/user-app/fcm-token', ['fcm_token' => 'token-from-firebase'])
            ->assertNoContent();

        $this->assertSame('token-from-firebase', session('fcm_token'));
    }

    /** @test */
    public function a_push_token_is_required()
    {
        $this->postJson('/user-app/fcm-token', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('fcm_token');
    }

    /** @test */
    public function older_app_builds_can_still_pass_the_token_in_the_url()
    {
        $this->get('/user-app?fcm_token=legacy-token')->assertStatus(200);

        $this->assertSame('legacy-token', session('fcm_token'));
    }

    /** @test */
    public function the_root_page_serves_the_app_catalog_inside_the_app()
    {
        // Login and logout redirect to "/", which must keep tagging inquiries as mobile.
        $this->withHeader('User-Agent', self::APP_UA)
            ->get('/')
            ->assertStatus(200)
            ->assertSee('name="source" value="mobile"', false);
    }

    /** @test */
    public function the_root_page_stays_the_web_catalog_in_a_browser()
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertDontSee('name="source" value="mobile"', false);
    }

    /** @test */
    public function the_catalog_does_not_download_gallery_media_up_front()
    {
        $html = $this->get('/user-app')->assertStatus(200)->getContent();

        $this->assertStringNotContainsString('preload="auto"', $html, 'The shop video must not preload with the page.');
        $this->assertDoesNotMatchRegularExpression(
            '#<img[^>]+\ssrc="[^"]*/images/shop_gallery_\d\.jpg"#',
            $html,
            'Gallery thumbnails must wait until the gallery is opened.'
        );
    }

    /** @test */
    public function the_catalog_does_not_block_on_the_table_view_libraries()
    {
        $html = $this->get('/user-app')->assertStatus(200)->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '#<script[^>]+src="[^"]*(jquery|dataTables)[^"]*"#i',
            $html,
            'jQuery/DataTables should load only when the table view is opened.'
        );
    }
}
