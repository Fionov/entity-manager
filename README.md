# Entity Manager

Mini-framework for CRUD Operations

## Deploy steps

1. Pull the code and open project directory
2. Execute composer install
```bash
composer install
```

3. Copy DB config and add your credentials to db.php
```bash
cp ./config/db.sample ./config/db.php
vi ./config/db.php
```

4. Execute migrations to create tables
```bash
./vendor/bin/phinx migrate
```

## Automated Testing

### PHPUnit tests:
1. Unit
```bash
vendor/bin/phpunit --testdox tests/Unit
```

2. Integration
```bash
vendor/bin/phpunit --testdox tests/Integration
```

*do not execute Integration tests on the Production environment, it will create/delete entities in the DB