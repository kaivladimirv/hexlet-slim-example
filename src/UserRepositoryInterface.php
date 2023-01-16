<?php

declare(strict_types=1);

namespace App;

interface UserRepositoryInterface
{
    public function all(): array;

    public function find(string $id): ?array;

    public function findByNickname(string $nickname): ?array;

    public function save(array $user): void;

    public function destroy(string $id): void;
}