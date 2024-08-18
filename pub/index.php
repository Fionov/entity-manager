<?php

require __DIR__.'/../vendor/autoload.php';

$userRepository = \App\Repositories\UserRepository::getInstance();
$collection = $userRepository->getCollection(
    where: [
        ['id', '>', 1],
    ],
    limit: 10,
);

foreach ($collection as $user) {
    echo $user->getId() . ' ' . $user->getName() . ' ' . $user->getEmail() . '<br/>' . PHP_EOL;
}
