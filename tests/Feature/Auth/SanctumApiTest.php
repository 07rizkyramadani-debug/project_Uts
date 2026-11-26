<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(fn () => RateLimiter::clear('api-auth'));

it('register API membuat akun berperan user dan mengembalikan token Sanctum', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Budi',
        'email' => 'budi@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin', // upaya privilege escalation harus diabaikan
    ]);

    $response->assertCreated()->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token']);
    expect(User::where('email', 'budi@example.com')->first()->role)->toBe('user');
});

it('register web juga tidak menerima role dari input', function () {
    $this->post('/register', [
        'name' => 'Sinta',
        'email' => 'sinta@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
    ])->assertRedirect();

    expect(User::where('email', 'sinta@example.com')->first()->role)->toBe('user');
});

it('register API menampilkan pesan validasi yang informatif', function () {
    $this->postJson('/api/register', ['email' => 'bukan-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('login API berhasil memberi token yang bisa dipakai mengakses /api/me', function () {
    $user = User::factory()->create(['password' => 'password123', 'role' => 'pustakawan']);

    $token = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password123'])
        ->assertOk()
        ->json('token');

    $this->withToken($token)->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.role', 'pustakawan');
});

it('login API gagal bila password salah', function () {
    $user = User::factory()->create(['password' => 'password123']);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'salah'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('endpoint terproteksi menolak request tanpa token (401)', function () {
    $this->getJson('/api/me')->assertUnauthorized();
    $this->getJson('/api/books')->assertUnauthorized();
});

it('logout API mencabut token', function () {
    $user = User::factory()->create(['password' => 'password123']);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/logout')->assertOk();

    expect($user->tokens()->count())->toBe(0);
});

it('RATE LIMIT: percobaan login ke-6 dalam semenit ditolak 429', function () {
    foreach (range(1, 5) as $i) {
        $this->postJson('/api/login', ['email' => 'x@example.com', 'password' => 'salah']);
    }

    $this->postJson('/api/login', ['email' => 'x@example.com', 'password' => 'salah'])
        ->assertStatus(429);
});

it('CORS: preflight dari origin yang diizinkan mendapat header Access-Control', function () {
    $this->call('OPTIONS', '/api/books', [], [], [], [
        'HTTP_ORIGIN' => 'http://localhost:8000',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ])->assertHeader('Access-Control-Allow-Origin', 'http://localhost:8000');
});

it('token API kedaluwarsa setelah masa berlaku habis', function () {
    $user = User::factory()->create();
    $token = $user->createToken('t')->plainTextToken;

    $this->withToken($token)->getJson('/api/me')->assertOk();

    $this->travel(25)->hours();
    app('auth')->forgetGuards();

    $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
});
