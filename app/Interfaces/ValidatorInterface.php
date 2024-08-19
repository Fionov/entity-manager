<?php

declare(strict_types=1);

namespace App\Interfaces;

interface ValidatorInterface
{
    /**
     * @return void
     */
    public function validate(): void;
}