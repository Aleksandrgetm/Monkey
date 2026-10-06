<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'scan:create-admin {email? : New administrator email address} {--name= : Alphanumeric username}';

    protected $description = 'Create an administrator with an interactively entered password; no default credentials';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Run interactively to enter the administrator password securely. No password argument or default password is supported.');

            return self::FAILURE;
        }

        $data = [
            'name' => $this->option('name') ?: $this->ask('Username'),
            'email' => strtolower(trim((string) ($this->argument('email') ?: $this->ask('Email address')))),
            'password' => $this->secret('Password (at least 12 characters)'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'regex:/^[\p{L}\p{N}]{3,30}$/u'],
            'email' => ['required', 'string', 'email', 'max:30', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 1,
            'status' => 1,
        ]);
        $this->info('Administrator created. Sign in using the application login form.');

        return self::SUCCESS;
    }
}
