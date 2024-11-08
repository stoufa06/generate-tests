<?php

use PHPUnit\Framework\TestCase;
use Stoufa06\GenerateTests\TestGenerator;

class TestGeneratorTest extends TestCase
{
    private $testGenerator;
    private $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary directory for testing
        $this->tempDir = sys_get_temp_dir() . '/testgenerator_' . uniqid();
        mkdir($this->tempDir, 0777, true);

        // Set up src and test directories within the temporary directory
        $srcDir = $this->tempDir . '/src';
        $testDir = $this->tempDir . '/tests';
        mkdir($srcDir);
        mkdir($testDir);

        // Place a mock TestGenerator.php file in the src directory
        file_put_contents($srcDir . '/TestGenerator.php', '<?php class TestGenerator {}');

        $this->testGenerator = new TestGenerator($srcDir, $testDir);
    }

    protected function tearDown(): void
    {
        $this->recursiveRemoveDirectory($this->tempDir);
    }

    private function recursiveRemoveDirectory($directory)
    {
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        ) as $fileinfo) {
            $action = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $action($fileinfo->getRealPath());
        }
        rmdir($directory);
    }

    public function testFileSetup()
    {
        $this->assertFileExists($this->tempDir . '/src/TestGenerator.php');
        $this->assertDirectoryExists($this->tempDir . '/tests');
    }

    public function testEnsureDirectoryExists()
    {
        $privateMethod = function ($directory) {
            /** @disregard  */
            return $this->ensureDirectoryExists($directory);
        };

        $destination = $this->tempDir . '/new_tests';
        $privateMethod->call($this->testGenerator, $destination);
        $this->assertDirectoryExists($destination);
    }

    public function testGetSourceFilesRetrievesPhpFiles()
    {
        $privateMethod = function ($directory) {
            /** @disregard  */
            return $this->getSourceFiles($directory);
        };

        $result = $privateMethod->call($this->testGenerator, $this->tempDir . '/src');
        $this->assertContains($this->tempDir . '/src/TestGenerator.php', $result);
    }

    public function testGenerateTestContentCreatesCorrectStructure()
    {
        $privateMethod = function ($className) {
            /** @disregard  */
            return $this->generateTestContent($className);
        };

        $expectedContent = <<<EOD
        <?php
        
        declare(strict_types=1);

        // Generated by script

        use PHPUnit\Framework\TestCase;

        class SampleClassTest extends TestCase
        {
            public function testExample()
            {
                \$this->assertTrue(true);
            }
        }
        EOD;

        $actualContent = $privateMethod->call($this->testGenerator, 'SampleClassTest');
        $this->assertEquals($expectedContent, $actualContent);
    }

    public function testGenerateCreatesExpectedTestFileStructure()
    {
        $this->testGenerator->generate();

        $expectedTestFile = $this->tempDir . '/tests/TestGeneratorTest.php';
        $this->assertFileExists($expectedTestFile);

        $expectedContent = <<<'EOD'
        <?php
        
        declare(strict_types=1);

        // Generated by script

        use PHPUnit\Framework\TestCase;

        class TestGeneratorTest extends TestCase
        {
            public function testExample()
            {
                $this->assertTrue(true);
            }
        }
        EOD;

        $this->assertStringEqualsFile($expectedTestFile, $expectedContent);
    }
}
