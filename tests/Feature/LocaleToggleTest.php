<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_arabic_is_the_default_locale_and_rtl_direction(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();

        $this->assertSame('ar', $admin->locale);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
    }

    public function test_user_can_switch_to_english_and_it_is_remembered(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect();

        $this->assertSame('en', $admin->fresh()->locale);

        $response = $this->actingAs($admin->fresh())->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('lang="en"', false);
        $response->assertSee('Dashboard');
    }

    public function test_guest_session_locale_does_not_require_login(): void
    {
        $this->post(route('locale.update'), ['locale' => 'en'])->assertRedirect();

        $response = $this->get(route('login'));
        $response->assertOk();
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('locale.update'), ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');

        $this->assertSame('ar', $admin->fresh()->locale);
    }
}
