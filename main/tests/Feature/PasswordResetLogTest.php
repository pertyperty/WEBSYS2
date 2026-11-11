<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PasswordResetLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_real_log_mail_reset_link_changes_the_password_and_cannot_be_reused(): void
    {
        Storage::fake('local');
        $disk = Storage::disk('local');
        $disk->makeDirectory('reset-check');
        config([
            'mail.default' => 'log',
            'mail.mailers.log.channel' => 'reset-check',
            'logging.channels.reset-check' => ['driver' => 'single', 'path' => $disk->path('reset-check/mail.log'), 'level' => 'debug'],
        ]);
        URL::forceRootUrl('https://checkpoint.example');
        URL::forceScheme('https');
        $this->app['mail.manager']->purge();
        $user = User::factory()->create(['email' => 'reset+staff@example.test']);

        try {
            $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();

            $disk->assertExists('reset-check/mail.log');
            $mail = $disk->get('reset-check/mail.log');
            $this->assertStringContainsString('To: '.$user->email, $mail);
            preg_match_all('~https?://[^\s<>"]+/reset-password/[A-Za-z0-9]+\?email=[^\s<>"]+~', $mail, $links);
            $this->assertNotEmpty($links[0], 'The real log transport must write a usable reset link.');
            $url = html_entity_decode($links[0][0]);
            $this->assertSame('checkpoint.example', parse_url($url, PHP_URL_HOST));
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $this->assertSame($user->email, $query['email']);
            $path = parse_url($url, PHP_URL_PATH);
            $this->get($path.'?'.parse_url($url, PHP_URL_QUERY))->assertOk()->assertSee('Reset password');
            $input = ['email' => $query['email'], 'token' => basename($path), 'password' => 'NewResetPassword123', 'password_confirmation' => 'NewResetPassword123'];
            $this->post('/reset-password', $input)->assertSessionHasNoErrors()->assertRedirect('/login');
            $this->assertTrue(Hash::check('NewResetPassword123', $user->fresh()->password));
            $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
            $this->post('/reset-password', [...$input, 'password' => 'SecondResetPassword456', 'password_confirmation' => 'SecondResetPassword456'])->assertSessionHasErrors('email');
            $this->assertTrue(Hash::check('NewResetPassword123', $user->fresh()->password));
        } finally {
            $this->app['mail.manager']->purge('log');
            Log::channel('reset-check')->getLogger()->close();
            Log::forgetChannel('reset-check');
            $disk->deleteDirectory('reset-check');
        }
    }
}
