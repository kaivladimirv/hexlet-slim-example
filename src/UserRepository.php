<?php

declare(strict_types=1);

namespace App;

class UserRepository implements UserRepositoryInterface
{
    private UserRepositoryInterface $repository;

    public function __construct(UserRepositoryInterface $repository)
    {

        $this->repository = $repository;
    }

    public function all(): array
    {
        return $this->repository->all();
    }

    public function find(string $id): ?array
    {
        return $this->repository->find($id);
    }

    public function findByNickname(string $nickname): ?array
    {
        return $this->repository->findByNickname($nickname);
    }

    public function save(array $user): void
    {
        $this->repository->save($user);
    }

    public function destroy(string $id): void
    {
        $this->repository->destroy($id);
    }
}