<?php

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ImportProductCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('import-product');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'test' => true,
            '--max-stock' => 10,
            '--min-price' => 5,
            '--max-price' => 1000,
            '--file' => '/public/files/test.csv'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(
            " Available products for processing: 15\n Successfully imported products: 13\n The products were not imported because criteria were not met: 2\n",
            $output
        );
        $this->assertSame('test', $kernel->getEnvironment());
    }
}
