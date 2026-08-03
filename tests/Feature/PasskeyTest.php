<?php

namespace Tests\Feature;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasskeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_passkey_register_with_inertia_header_returns_redirect(): void
    {
        $user = User::create([
            'name' => 'Passkey User',
            'email' => 'passkey@finz.app',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($user)->withSession(['passkey_register_challenge' => 'test_challenge']);

        $response = $this->withHeaders(['X-Inertia' => 'true'])
            ->post('/passkeys/register', [
                'id' => 'cred_123',
                'rawId' => 'cred_123',
                'response' => ['clientDataJSON' => 'abc', 'attestationObject' => 'xyz'],
                'name' => 'My Phone',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('passkeys', [
            'user_id' => $user->id,
            'credential_id' => 'cred_123',
            'name' => 'My Phone',
        ]);
    }

    public function test_passkey_login_with_inertia_header_returns_redirect(): void
    {
        $user = User::create([
            'name' => 'Passkey User',
            'email' => 'passkey@finz.app',
            'password' => bcrypt('password123'),
        ]);

        Passkey::create([
            'user_id' => $user->id,
            'name' => 'My Key',
            'credential_id' => 'cred_999',
            'public_key' => json_encode(['test' => 1]),
            'counter' => 0,
        ]);

        $this->withSession(['passkey_login_challenge' => 'login_challenge']);

        $response = $this->withHeaders(['X-Inertia' => 'true'])
            ->post('/passkeys/login', [
                'id' => 'cred_999',
                'response' => ['clientDataJSON' => 'abc', 'signature' => 'sig'],
            ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }
}
