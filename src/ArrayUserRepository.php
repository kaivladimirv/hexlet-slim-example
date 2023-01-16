<?php

declare(strict_types=1);

namespace App;

class ArrayUserRepository implements UserRepositoryInterface
{
    private array $users;

    public function __construct(array $users)
    {
        $this->users = $users;
    }

    public function all(): array
    {
        return collect($this->users)->keyBy('id')->all();
    }

    public function find(string $id): ?array
    {
        $users = $this->all();

        return $users[$id] ?? null;
    }

    public function findByNickname(string $nickname): ?array
    {
        return collect($this->all())
            ->firstWhere('nickname', $nickname);
    }

    public function save(array $user): void
    {
        $this->users[$user['id']] = $user;
    }

    public function destroy(string $id): void
    {
        unset($this->users[$id]);
    }
}
