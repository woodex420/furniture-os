<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Provisioning\ProvisionFirstOwner;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\Console\Question\Question;

final class ProvisionFirstOwnerCommand extends Command
{
    protected $signature = 'furniture:provision-owner';

    protected $description = 'Interactively provision the first owner on a new, empty Furniture installation';

    public function handle(ProvisionFirstOwner $provisioner): int
    {
        // Do not accept passwords in arguments, environment variables, pipes or CI logs.
        if (! $this->input->isInteractive() || ! defined('STDIN') || ! defined('STDOUT')
            || ! function_exists('stream_isatty') || ! stream_isatty(STDIN) || ! stream_isatty(STDOUT)) {
            $this->error('Owner setup requires an interactive terminal with hidden input.');

            return self::FAILURE;
        }

        if ($this->ask('For a NEW empty Furniture installation, type INITIALIZE to continue') !== 'INITIALIZE') {
            $this->warn('Owner setup cancelled.');

            return self::FAILURE;
        }

        $name = $this->ask('Owner name');
        $email = $this->ask('Owner email');
        try {
            // Hidden fallback is disabled: never prompt visibly if the terminal cannot hide input.
            $password = $this->readHidden('New password (12–72 bytes)');
            $confirmation = $this->readHidden('Confirm new password');
        } catch (RuntimeException $exception) {
            $this->error('Hidden terminal input is unavailable. No account was created.');

            return self::FAILURE;
        }

        if (! is_string($name) || ! is_string($email) || ! is_string($password)
            || ! is_string($confirmation) || ! hash_equals($password, $confirmation)) {
            $this->error('Details were incomplete or passwords did not match.');

            return self::FAILURE;
        }

        try {
            $provisioner->provision($name, $email, $password);
        } catch (ValidationException $exception) {
            $this->error('Invalid name, email, or password length. No account was created.');

            return self::FAILURE;
        } catch (RuntimeException $exception) {
            $this->error('Initial owner setup is unavailable. No account was created.');

            return self::FAILURE;
        } finally {
            unset($password, $confirmation);
        }

        $this->info('Initial owner created. Workspace features remain pending.');

        return self::SUCCESS;
    }

    private function readHidden(string $prompt): mixed
    {
        $question = new Question($prompt.': ');
        $question->setHidden(true)->setHiddenFallback(false)->setTrimmable(false);
        $question->setNormalizer(fn (?string $value): ?string => $value === null ? null : rtrim($value, "\r\n"));

        return $this->output->askQuestion($question);
    }
}
