<?php

declare(strict_types=1);

namespace App;

class FileUserRepository implements UserRepositoryInterface
{
    private const PATH_TO_FILE = './storage/users.txt';

    public function all(): array
    {
        if (!file_exists(self::PATH_TO_FILE)) {
            return [];
        }

        if (!$data = file_get_contents(self::PATH_TO_FILE)) {
            return [];
        }

        $users = json_decode($data, true);

        return collect($users)->keyBy('id')->all();
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
        $users = $this->all();

        $users[$user['id']] = $user;

        $this->saveAll($users);
    }

    public function destroy(string $id): void
    {
        $users = $this->all();

        unset($users[$id]);

        $this->saveAll($users);
    }

    private function saveAll(array $users): void
    {
        file_put_contents(self::PATH_TO_FILE, json_encode($users));
    }
}