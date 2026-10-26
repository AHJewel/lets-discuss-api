<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function register(array $data): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Origin', 'http://lets-discuss.localhost')->postJson('/api/register', $data);
    }

    public function test_guest_can_register_and_is_logged_in(): void
    {
        $this->register($this->payload())
            ->assertCreated()
            ->assertJsonPath('data.email', 'ada@example.com')
            ->assertJsonPath('data.role', 'user')
            ->assertJsonMissingPath('data.password');

        $user = User::firstWhere('email', 'ada@example.com');
        $this->assertNotNull($user);
        $this->assertNotSame('Str0ng-Passw0rd!', $user->password);
        $this->assertAuthenticatedAs($user);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->register($this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertSame(1, User::count());
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->register($this->payload(['password' => 'weak', 'password_confirmation' => 'weak']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertSame(0, User::count());
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->register($this->payload(['password_confirmation' => 'Different-Passw0rd!']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_required_fields_return_per_field_errors(): void
    {
        $this->register([])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_role_cannot_be_mass_assigned(): void
    {
        $this->register($this->payload(['role' => 'admin']))
            ->assertCreated()
            ->assertJsonPath('data.role', 'user');

        $this->assertSame(UserRole::User, User::firstWhere('email', 'ada@example.com')->role);
    }

    public function test_registration_is_throttled_per_ip(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->register($this->payload(['email' => "user{$i}@example.com"]))->assertCreated();
        }

        $this->register($this->payload(['email' => 'user6@example.com']))
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');

        $this->assertSame(5, User::count());
    }
}
