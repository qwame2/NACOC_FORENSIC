<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected bool $followLegacyReviewRedirects = true;

    public function get($uri, array $headers = [])
    {
        if (
            $this->followLegacyReviewRedirects
            && is_string($uri)
            && preg_match('#^/?cases/[^/?]+/review(\?|$)#', $uri)
        ) {
            return $this->followingRedirects()->parentGet($uri, $headers);
        }

        return parent::get($uri, $headers);
    }

    protected function parentGet($uri, array $headers = [])
    {
        return parent::get($uri, $headers);
    }
}
