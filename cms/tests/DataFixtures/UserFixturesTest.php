<?php

namespace App\Tests\DataFixtures;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Asserts the state produced by UserFixtures - loaded once into the test
 * database ahead of the suite (see Makefile's `test` target), not
 * re-executed per test.
 */
class UserFixturesTest extends KernelTestCase
{
    public function testAdminUserExistsWithExpectedRoleAndPassword(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var UserRepository $repository */
        $repository = $container->get(UserRepository::class);
        $user = $repository->findOneBy(['email' => 'admin@cms.dev']);

        self::assertInstanceOf(User::class, $user);
        self::assertContains('ROLE_SUPER_ADMIN', $user->getRoles());

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($user, '1234567890'));
    }

    public function testExactlyOneAdminUserExists(): void
    {
        self::bootKernel();

        /** @var UserRepository $repository */
        $repository = static::getContainer()->get(UserRepository::class);

        self::assertCount(1, $repository->findAll());
    }
}
