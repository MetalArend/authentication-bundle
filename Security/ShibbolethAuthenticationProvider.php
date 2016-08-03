<?php

namespace Kuleuven\AuthenticationBundle\Security;

use Kuleuven\AuthenticationBundle\Traits\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ShibbolethAuthenticationProvider implements AuthenticationProviderInterface, LoggerAwareInterface
{
    use LoggerTrait;

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * @var UserCheckerInterface
     */
    protected $userChecker;

    /**
     * @param UserProviderInterface $userProvider
     * @param UserCheckerInterface  $userChecker
     */
    public function __construct(UserProviderInterface $userProvider, UserCheckerInterface $userChecker)
    {
        $this->userProvider = $userProvider;
        $this->userChecker = $userChecker;
    }

    /**
     * @inheritdoc
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            $this->log(basename(__FILE__) . ' - ' . sprintf('Token not supported: %s', $token));
            return null;
        }

        if (!$user = $token->getUser()) {
            $this->log(basename(__FILE__) . ' - ' . sprintf('User not found in token: %s', $token));
            throw new BadCredentialsException('User not found in request.');
        }

        // Reattach the objects to Doctrine
        $username = $token->getUsername();

        try {
            if ($user instanceof UserInterface) {
                $token->setUser($this->userProvider->refreshUser($user));
                $user = $token->getUser();
            } else {
                $user = $this->userProvider->loadUserByUsername($username);
            }
            if (empty($user) || !$user instanceof UserInterface) {
                $this->log(basename(__FILE__) . ' - ' . sprintf('User not found for username "%s"', $username));
                throw new AuthenticationException('Shibboleth authentication failed.');
            }
            $this->log(basename(__FILE__) . ' - ' . sprintf('User found for username "%s": %s', $username, $user));

            foreach ($token->getRoles() as $role) {
                if ($role instanceof SwitchUserRole) {
                    $source = $role->getSource();
                    $sourceUser = $source->getUser();
                    if ($sourceUser instanceof UserInterface) {
                        $source->setUser($this->userProvider->refreshUser($sourceUser));
                    }
                }
            }
        } catch (UsernameNotFoundException $notFound) {
            $this->log(basename(__FILE__) . ' - ' . sprintf('User not found for username "%s": %s', $username, $notFound->getMessage()));
            throw new AuthenticationException('Shibboleth authentication failed.', 0, $notFound);
        }

        if ($user instanceof UserInterface) {
            $this->userChecker->checkPostAuth($user);
        }

        $authenticatedToken = new KuleuvenUserToken($user, $user->getAttributes(), $token->getRoles());
        $authenticatedToken->setAuthenticated(true);
        $this->log(basename(__FILE__) . ' - ' . sprintf('Token authenticated for username "%s": %s', $authenticatedToken->getUsername(), $authenticatedToken));

        return $authenticatedToken;
    }

    /**
     * @inheritdoc
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof KuleuvenUserToken;
    }
}
