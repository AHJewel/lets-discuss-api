<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_default_to_user_role(): void
    {
        $user = User::factory()->create()->fresh();

        $this->assertSame(UserRole::User, $user->role);
        $this->assertNull($user->suspended_at);
    }

    public function test_duplicate_emails_are_rejected_by_the_database(): void
    {
        User::factory()->create(['email' => 'dupe@example.com']);

        $this->expectException(QueryException::class);

        User::factory()->create(['email' => 'dupe@example.com']);
    }
}
