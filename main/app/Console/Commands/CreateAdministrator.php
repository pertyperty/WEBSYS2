<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'checkpoint:create-admin';

    protected $description = 'Create a new administrator using private interactive password entry';

    public function handle(): int
    {
        $input = [
            'name' => $this->ask('Administrator name'),
            'email' => strtolower(trim((string) $this->ask('Administrator email'))),
            'password' => $this->secret('Password (12+ characters with letters and numbers)'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::default()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->fill(collect($input)->only(['name', 'email', 'password'])->all());
        $user->role = UserRole::Admin;
        $user->is_active = true;
        $user->save();

        $this->info('Administrator created. Sign in using the credentials you entered.');

        return self::SUCCESS;
    }
}
