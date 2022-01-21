<?php

namespace OpenSourceSupportReport;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class WhoamiTest extends TestCase implements CommandTesterInterface
{
    use CommandTesterTrait;

    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    /**
     * Sanity-check: who are we authenticated as
     */
    public function testWhoami()
    {
        $output = $this->executeExpectOK(['whoami']);
        $this->assertContains('Authenticated as pantheon-ci-bot', $output);
    }
}
