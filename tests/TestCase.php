<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
            \Illuminate\Support\Facades\DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', function ($date, $format) {
                if (!$date) return null;
                $format = str_replace(['%Y', '%m', '%d', '%H', '%i', '%s'], ['Y', 'm', 'd', 'H', 'i', 's'], $format);
                return date($format, strtotime($date));
            });
        }
    }
}
