<?php

namespace Tests\_support\Helper;

use Codeception\Module;

/**
 * Functional helper for container tests.
 *
 * @package ByteEver/Container
 */
class Functional extends Module
{
    /**
     * Get the test plugin file path.
     *
     * @return string
     */
    public function getTestPluginFile(): string
    {
        return codecept_data_dir() . 'test-plugin.php';
    }
}
