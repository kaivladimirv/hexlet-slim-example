<?php

declare(strict_types=1);

namespace App;

class Validator
{
    public function validate(array $data): array
    {
        $errors = [];

        if (empty($data['nickname'])) {
            $errors['nickname'] = 'Nickname is empty';
        } elseif (strlen($data['nickname']) <= 4) {
            $errors['nickname'] = 'Nickname must be grater than 4 characters';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'Email is empty';
        }

        return $errors;
    }
}