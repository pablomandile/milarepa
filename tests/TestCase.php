<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * @mixin \PHPUnit\Framework\Assert
 */

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}
