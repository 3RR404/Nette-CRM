<?php

declare(strict_types=1);

namespace App\Model\User;

use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;

final class UserAuthenticator implements Authenticator
{
    public function __construct(
        private readonly UserManager $userManager,
        private readonly Passwords $passwords,
    ) {
    }

    public function authenticate(string $user, string $password): IIdentity
    {
        $row = $this->userManager->getByEmail($user);

        if (!$row) {
            throw new AuthenticationException('Invalid credentials.', self::IDENTITY_NOT_FOUND);
        }

        if (!$row->active) {
            throw new AuthenticationException('Account is disabled.', self::INVALID_CREDENTIAL);
        }

        if (!$this->passwords->verify($password, $row->password_hash)) {
            throw new AuthenticationException('Invalid credentials.', self::INVALID_CREDENTIAL);
        }

        $this->userManager->updateLastLogin($row->id);

        return new SimpleIdentity($row->id, [$row->role], [
            'email' => $row->email,
            'first_name' => $row->first_name,
            'last_name' => $row->last_name,
            'full_name' => $row->first_name . ' ' . $row->last_name,
            'role' => $row->role,
            'avatar' => $row->avatar,
        ]);
    }
}
