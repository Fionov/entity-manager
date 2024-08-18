<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Exceptions\IncorrectDataException;
use App\Exceptions\NoSuchEntityException;
use App\Interfaces\Data\UserInterface;
use App\Models\User;
use App\Repositories\UserRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class UserRepositoryTest extends MockeryTestCase
{
    /** @var string */
    private const EXISTING_USER_EMAIL = 'user@domain.com';
    private const NOT_EXISTING_USER_EMAIL = 'not-email';

    /** @var int */
    private const EXISTING_USER_ID = 1;
    private const NOT_EXISTING_USER_ID = -1;

    /** @var \App\Repositories\UserRepository */
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = UserRepository::getInstance();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function testGetByIdReturnsUser(): void
    {
        $user = $this->userRepository->getById(self::EXISTING_USER_ID);
        $this->assertInstanceOf(UserInterface::class, $user);
        $this->assertEquals(self::EXISTING_USER_ID, $user->getId());
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function testGetByIdThrowsExceptionWhenUserNotFound(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->userRepository->getById(self::NOT_EXISTING_USER_ID);
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \Random\RandomException
     */
    public function testGetByIdCachedUser(): void
    {
        $newNotes = $this->getUniqueString();

        $user = $this->userRepository->getById(self::EXISTING_USER_ID);
        $user->setNotes($newNotes);

        $cachedUser = $this->userRepository->getById(self::EXISTING_USER_ID);

        $this->assertSame($cachedUser->getNotes(), $newNotes);
        $this->assertEquals(self::EXISTING_USER_ID, $cachedUser->getId());
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \Random\RandomException
     */
    public function testGetByIdForceReload(): void
    {
        $newNotes = $this->getUniqueString();

        $user = $this->userRepository->getById(self::EXISTING_USER_ID);
        $user->setNotes($newNotes);

        $reloadedUser = $this->userRepository->getById(self::EXISTING_USER_ID, true);

        $this->assertNotSame($reloadedUser->getNotes(), $newNotes);
        $this->assertEquals(self::EXISTING_USER_ID, $reloadedUser->getId());
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function testGetByEmailReturnsUser(): void
    {
        $user = $this->userRepository->getByEmail(self::EXISTING_USER_EMAIL);
        $this->assertInstanceOf(UserInterface::class, $user);
        $this->assertEquals(self::EXISTING_USER_EMAIL, $user->getEmail());
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function testGetByEmailThrowsExceptionWhenUserNotFound(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->userRepository->getByEmail(self::NOT_EXISTING_USER_EMAIL);
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \Random\RandomException
     */
    public function testGetByEmailCachedUser(): void
    {
        $newNotes = $this->getUniqueString();

        $user = $this->userRepository->getByEmail(self::EXISTING_USER_EMAIL);
        $user->setNotes($newNotes);

        $cachedUser = $this->userRepository->getByEmail(self::EXISTING_USER_EMAIL);

        $this->assertSame($cachedUser->getNotes(), $newNotes);
        $this->assertEquals(self::EXISTING_USER_EMAIL, $cachedUser->getEmail());
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \Random\RandomException
     */
    public function testGetByEmailForceReload(): void
    {
        $newNotes = $this->getUniqueString();

        $user = $this->userRepository->getByEmail(self::EXISTING_USER_EMAIL);
        $user->setNotes($newNotes);

        $reloadedUser = $this->userRepository->getByEmail(self::EXISTING_USER_EMAIL, true);

        $this->assertNotSame($reloadedUser->getNotes(), $newNotes);
        $this->assertEquals(self::EXISTING_USER_EMAIL, $reloadedUser->getEmail());
    }

    /**
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \Random\RandomException
     */
    public function testSaveUserAndCache(): void
    {
        $uniqueString = $this->getUniqueString(16);
        $newEmail = $uniqueString . '_test@example.com';
        $newName = $uniqueString . '_test';

        $user = new User([
            User::EMAIL => $newEmail,
            User::NAME => $newName,
        ]);

        $savedUser = $this->userRepository->save($user);
        $this->assertNotNull($savedUser->getId());

        $cachedUserById = $this->userRepository->getById($savedUser->getId());
        $this->assertSame($savedUser, $cachedUserById);

        $cachedUserByEmail = $this->userRepository->getByEmail($savedUser->getEmail());
        $this->assertSame($savedUser, $cachedUserByEmail);
    }

    /**
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \Random\RandomException
     */
    public function testSaveUpdatesExistingUser(): void
    {
        $uniqueString = $this->getUniqueString(16);
        $initialEmail = $uniqueString . '_test@example.com';
        $initialName = $uniqueString . '_test';

        $user = new User();
        $user->setEmail($initialEmail);
        $user->setName($initialName);
        $this->userRepository->save($user);

        $uniqueString = $this->getUniqueString(16);
        $newEmail = $uniqueString . '_test@example.com';
        $newName = $uniqueString . '_test';

        $user->setName($newName);
        $user->setEmail($newEmail);
        $updatedUser = $this->userRepository->save($user);

        //Name was updated
        $this->assertEquals($newName, $updatedUser->getName());

        //Cache by id was updated
        $cachedUser = $this->userRepository->getById($updatedUser->getId());
        $this->assertSame($updatedUser, $cachedUser);
        $this->assertEquals($newName, $cachedUser->getName());

        //Cache by email was updated
        $cachedByEmailUser = $this->userRepository->getByEmail($updatedUser->getEmail());
        $this->assertSame($updatedUser, $cachedByEmailUser);
        $this->assertEquals($newEmail, $cachedByEmailUser->getEmail());
    }

    public function testSaveDataException(): void
    {
        $incorrectEmail = 'not_email_format';
        $incorrectName = 'incorrect_format_name';


        $user = new User();
        $user->setEmail($incorrectEmail);
        $user->setName($incorrectName);

        $this->expectException(IncorrectDataException::class);
        $this->userRepository->save($user);
    }

    /**
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \Random\RandomException
     */
    public function testDeleteByIdSoft(): void
    {
        $uniqueString = $this->getUniqueString(16);
        $newEmail = $uniqueString . '_test@example.com';
        $newName = $uniqueString . '_test';

        $user = new User();
        $user->setEmail($newEmail);
        $user->setName($newName);
        $this->userRepository->save($user);

        $result = $this->userRepository->deleteById($user->getId(), true);
        $this->assertTrue($result);

        $this->userRepository->getById($user->getId(), true);
        $this->assertTrue($user->getIsDeleted());
    }

    /**
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \Random\RandomException
     */
    public function testDeleteByIdHard(): void
    {
        $uniqueString = $this->getUniqueString(16);
        $newEmail = $uniqueString . '_test@example.com';
        $newName = $uniqueString . '_test';

        $user = new User();
        $user->setEmail($newEmail);
        $user->setName($newName);
        $this->userRepository->save($user);

        $result = $this->userRepository->deleteById($user->getId(), false);
        $this->assertTrue($result);

        $this->expectException(NoSuchEntityException::class);
        $this->userRepository->getById($user->getId());
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function testDeleteByIdNotExistUser(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->userRepository->deleteById(self::NOT_EXISTING_USER_ID);
    }

    /**
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     * @throws \Random\RandomException
     */
    public function testGetCollectionReturnUsersWithLimitOffset(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $uniqueString = $this->getUniqueString(16);
            $newEmail = $uniqueString . '_test@example.com';
            $newName = $uniqueString . '_test';

            $user = new User();
            $user->setEmail($newEmail);
            $user->setName($newName);
            $this->userRepository->save($user);
        }

        $result = $this->userRepository->getCollection(limit: 3, offset: 0);

        $this->assertCount(3, $result);
        $this->assertInstanceOf(User::class, $result[0]);
    }

    /**
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     * @throws \Random\RandomException
     */
    public function testGetCollectionReturnUsersWithWhere(): void
    {
        $emailIgnore = null;
        for ($i = 0; $i < 3; $i++) {
            $uniqueString = $this->getUniqueString(16);
            $newEmail = $uniqueString . '_test@example.com';
            $newName = $uniqueString . '_test';


            $user = new User();
            $user->setEmail($newEmail);
            $user->setName($newName);
            $this->userRepository->save($user);

            if (!$emailIgnore) {
                $emailIgnore = $user->getEmail();
            }
        }

        $where = [
            [User::ID, '>', 1],
            [User::EMAIL, '!=', $emailIgnore],
        ];

        $result = $this->userRepository->getCollection(where: $where);

        foreach ($result as $user) {
            $this->assertGreaterThan(1, $user->getId());
            $this->assertNotEquals($emailIgnore, $user->getEmail());
        }
    }

    /**
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     * @throws \Random\RandomException
     */
    public function testGetCollectionReturnUsersWithSorting(): void
    {
        $emailWhere = [];
        for ($i = 0; $i < 3; $i++) {
            $uniqueString = $this->getUniqueString(16);
            $newEmail = $uniqueString . '_test@example.com';
            $newName = $uniqueString . '_test';

            $user = new User();
            $user->setEmail($newEmail);
            $user->setName($newName);
            $user->setNotes('Note' . $i);
            $this->userRepository->save($user);

            $emailWhere[] = $user->getEmail();
        }

        $where = [
            [User::EMAIL, 'IN', $emailWhere],
        ];

        $result = $this->userRepository->getCollection(where: $where, ordering: [User::NOTES => 'DESC']);

        $this->assertEquals('Note2', $result[0]->getNotes());
        $this->assertEquals('Note1', $result[1]->getNotes());
        $this->assertEquals('Note0', $result[2]->getNotes());
    }

    /**
     * @param int $bytesLength
     * @return string
     * @throws \Random\RandomException
     */
    private function getUniqueString(int $bytesLength = 64): string
    {
        return bin2hex(random_bytes($bytesLength));
    }
}