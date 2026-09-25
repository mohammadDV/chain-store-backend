<?php

use Tests\Support\TestHelpers;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(TestHelpers::class)
    ->in('Feature', 'Unit');
