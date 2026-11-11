<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operator;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $this->customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);
    }

    public function test_authenticated_user_can_view_news_index(): void
    {
        News::create([
            'title' => 'Suez Canal Transit Protocol Update',
            'slug' => 'suez-canal-transit-protocol-update',
            'category' => 'PORT_ADVISORY',
            'summary' => 'Updated convoy transit schedule.',
            'content' => 'Full detailed guidance text.',
            'published_at' => now(),
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->customer)->get(route('news.index'));

        $response->assertOk()
            ->assertSee('Suez Canal Transit Protocol Update')
            ->assertSee('Port Advisory');
    }

    public function test_authenticated_user_can_view_news_detail(): void
    {
        $news = News::create([
            'title' => 'Gale Warning in Singapore Strait',
            'slug' => 'gale-warning-singapore-strait',
            'category' => 'WEATHER_ALERT',
            'summary' => 'Weather alert for marine vessels.',
            'content' => 'Full weather advisory content text.',
            'published_at' => now(),
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->customer)->get(route('news.show', $news->slug));

        $response->assertOk()
            ->assertSee('Gale Warning in Singapore Strait')
            ->assertSee('Full weather advisory content text');
    }

    public function test_admin_can_publish_new_advisory(): void
    {
        $payload = [
            'title' => 'New Port Tariff Regulations 2026',
            'category' => 'REGULATORY',
            'summary' => 'Summary of new port charges.',
            'content' => 'Detailed regulation breakdown.',
            'is_breaking' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('news.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('news', [
            'title' => 'New Port Tariff Regulations 2026',
            'category' => 'REGULATORY',
            'is_breaking' => true,
        ]);
    }

    public function test_admin_can_delete_advisory(): void
    {
        $news = News::create([
            'title' => 'Obsolete Notice',
            'slug' => 'obsolete-notice',
            'category' => 'PORT_ADVISORY',
            'summary' => 'Old notice.',
            'content' => 'Old content.',
            'published_at' => now(),
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('news.destroy', $news));

        $response->assertRedirect(route('news.index'));
        $this->assertDatabaseMissing('news', [
            'id' => $news->id,
        ]);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('news.index'));
        $response->assertRedirect(route('login'));
    }
}
