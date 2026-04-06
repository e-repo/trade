<?php

declare(strict_types=1);

namespace CoreKit\Application\Event;

interface UserCreatedOrUpdatedEventInterface
{
    public function getId(): string;

    public function getFirstname(): string;

    public function geLastname(): ?string;

    public function getEmail(): string;

    public function getStatus(): string;

    public function getRole(): string;
}
