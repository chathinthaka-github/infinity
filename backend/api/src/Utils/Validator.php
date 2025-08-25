<?php
declare(strict_types=1);

namespace App\Utils;

/**
 * Class Validator
 * Small validators used by controllers
 */
class Validator
{
    public static function validateRegister(array $data): array
    {
        $errors = [];

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen((string)$data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        // optional: whatsapp number format
        if (!empty($data['whatsapp_number']) && !preg_match('/^\+?[0-9]{6,15}$/', $data['whatsapp_number'])) {
            $errors['whatsapp_number'] = 'Invalid phone number';
        }

        return $errors;
    }

    public static function validateLogin(array $data): array
    {
        $errors = [];
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        }
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }
        return $errors;
    }
}
