<?php

namespace User\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Xmlps\DataObject\DataObject;
use Zend\Mvc\I18n\Translator;

define('USER_LEVEL_REGISTERED', 0);
define('USER_LEVEL_REGISTRATION_CONFIRMED', 1);

/**
 * User
 *
 * @ORM\Entity
 * @ORM\Table(name="user")
 * @ORM\HasLifecycleCallbacks
 */
class User extends DataObject
{
    protected $translator;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=false, unique=true)
     */
    protected $email;

    /**
     * @ORM\Column(type="string", length=40, nullable=false)
     */
    protected $password;

    /**
     * @ORM\Column(type="string", length=13, nullable=false)
     */
    protected $passwordSalt;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $registrationDate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $lastLogin;

    /**
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $level;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $role;

    /**
     * Constructor
     *
     * @param Translator $translator
     * @return void
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Sets the registration date timestamp
     *
     * @return void
     *
     * @ORM\PrePersist
     */
    public function initRegistrationDate()
    {
        if ($this->registrationDate === null) {
            $this->registrationDate = time();
        }
    }

    /**
     * Initializes the level
     *
     * @return void
     *
     * @ORM\PrePersist
     */
    public function initLevel()
    {
        if ($this->level === null) {
            $this->level = USER_LEVEL_REGISTERED;
        }
    }

    /**
     * Initializes the role
     *
     * @return void
     *
     * @ORM\PrePersist
     */
    public function initRole()
    {
        if ($this->role === null) {
            $this->role = 'member';
        }
    }

    /**
     * Sets lastLogin to the current timestamp
     *
     * @return void
     */
    public function setLastLogin()
    {
        $this->lastLogin = time();
    }

    /**
     * Returns a users password salt. If the salt is not set it will generate it
     *
     * @return string Password salt
     */
    public function getPasswordSalt()
    {
        if ($this->passwordSalt === null) {
            $this->passwordSalt = uniqid('');
        }

        return $this->passwordSalt;
    }

    /**
     * Password setter. It will hash the password automatically.
     *
     * @param string $password Password
     *
     * @return void
     */
    public function setPassword($password)
    {
        $this->password = self::hashPassword($password, $this->getPasswordSalt());
    }

    /**
     * Hashes a password
     *
     * @param mixed $password Unhased password
     * @param mixed $salt Salt
     *
     * @return string Hashed password
     */
    protected static function hashPassword($password, $salt)
    {
        return sha1($password . $salt);
    }

    /**
     * Validates a password for a given user
     *
     * @param User $user User instance
     * @param mixed $password Plaintext password
     * @return bool Whether or not the password is valid
     */
    public static function validatePassword(User $user, $password)
    {
        return (
            $user->password == self::hashPassword($password, $user->getPasswordSalt())
        );
    }

    /**
     * Checks if the user is an administrator
     *
     * @return bool Whether or not the user is an administrator
     */
    public function isAdministrator()
    {
        return $this->role == 'administrator';
    }

    /**
     * Maps user levels to display strings
     *
     * @return array map of user levels to display strings
     */
    public function getLevelMap()
    {
        return array(
            USER_LEVEL_REGISTERED => $this->translator->translate('user.user.level.registered'),
            USER_LEVEL_REGISTRATION_CONFIRMED => $this->translator->translate('user.user.level.registrationConfirmed'),
        );
    }

    /**
     * Maps user roles to display strings
     *
     * @return array map of user roles to display strings
     */
    public function getRoleMap()
    {
        return array(
            'member' => $this->translator->translate('user.user.role.member'),
            'administrator' => $this->translator->translate('user.user.role.administrator'),
        );
    }
}
