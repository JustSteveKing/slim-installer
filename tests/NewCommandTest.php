<?php declare(strict_types=1);

namespace JustSteveKing\Slim\Installer\Console\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use JustSteveKing\Slim\Installer\Console\NewCommand;

class NewCommandTest extends TestCase
{
    /**
     * @test
     */
    public function it_will_create_a_new_slim_project()
    {
        $scaffoldDirectoryName = 'tests-output/slim-default';
        $scaffoldDirectory = __DIR__ . '/../'.$scaffoldDirectoryName;

        if (file_exists($scaffoldDirectory)) {
            if (PHP_OS_FAMILY == 'Windows') {
                exec("rd /s /q \"$scaffoldDirectory\"");
            } else {
                exec("rm -rf \"$scaffoldDirectory\"");
            }
        }

        $app = new Application('Slim PHP Installer');
        $app->add(new NewCommand);

        $tester = new CommandTester($app->find('new'));

        $statusCode = $tester->execute([
            'name' => $scaffoldDirectoryName
        ]);

        $this->assertSame(0, $statusCode);
        $this->assertDirectoryExists($scaffoldDirectory.'/vendor');
        $this->assertFileExists($scaffoldDirectory.'/public/index.php');
    }
}
