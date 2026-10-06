<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_api_routes_return_json_unauthorized_without_an_accept_header(): void
    {
        $document = Document::factory()->create();
        $routes = ['/api/auth/me', '/api/documents/'.$document->id.'/file', '/api/documents/'.$document->id.'/download', '/api/admin/users'];
        foreach ($routes as $route) {
            foreach ([[], ['Accept' => 'text/html']] as $headers) {
                $this->get($route, $headers)->assertUnauthorized()->assertHeader('Content-Type', 'application/json')->assertExactJson(['message' => 'Unauthenticated.']);
            }
        }
    }

    public function test_deleted_account_session_receives_json_unauthorized_without_an_accept_header(): void
    {
        $user = User::factory()->create(['password' => 'safePassword']);
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'safePassword'])->assertOk();
        $this->deleteJson('/api/profile', ['current_password' => 'safePassword', 'confirmed' => true])->assertNoContent();
        $this->get('/api/auth/me')->assertUnauthorized()->assertHeader('Content-Type', 'application/json')->assertExactJson(['message' => 'Unauthenticated.']);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_csrf_bootstrap_protects_login_and_authenticated_mutations(): void
    {
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $user = User::factory()->create(['password' => 'safePassword']);
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'safePassword'])->assertStatus(419);
        $response = $this->getJson('/api/auth/csrf')->assertOk()->assertJsonStructure(['token']);
        $token = $response->json('token');
        $this->withSession(['_token' => $token])->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'safePassword'], ['X-CSRF-TOKEN' => $token])->assertOk();
        $this->patchJson('/api/profile', ['name' => 'ChangedName'])->assertStatus(419);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => $user->name]);
        $token = $this->getJson('/api/auth/csrf')->assertOk()->json('token');
        $this->withSession(['_token' => $token])->patchJson('/api/profile', ['name' => 'ChangedName'], ['X-CSRF-TOKEN' => $token])->assertOk();
    }
}
