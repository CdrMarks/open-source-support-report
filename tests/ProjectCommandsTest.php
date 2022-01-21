<?php

namespace OpenSourceSupportReport;

use Symfony\Component\Console\Output\BufferedOutput;

class ProjectCommandsTest extends CommandsTestBase
{
    use CommandTesterTrait;

    /**
     * Prepare to test our commandfile
     */
    public function setUp()
    {
        $commandClasses = [
            \OpenSourceSupportReport\Cli\ProjectCommands::class,
            \OpenSourceSupportReport\Cli\TestUtilCommands::class,
            \Hubph\Cli\HubphCommands::class,
        ];
	}

    public function tearDown()
    {
    }
}
