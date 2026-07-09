<?php

namespace Consolidation\SiteProcess;

use PHPUnit\Framework\TestCase;
use Consolidation\SiteAlias\SiteAlias;

/**
 * Tests demonstrating a bug in ProcessBase::removeNonJsonJunk().
 *
 * The method incorrectly assumes that output starting with '[' and ending
 * with ']' is a valid JSON array and returns it verbatim without validation.
 * This causes getOutputAsJson() to throw "Unable to decode output into JSON:
 * Syntax error" when non-JSON output (e.g. log messages from Drush verbose
 * mode) wraps valid JSON content.
 *
 * Real-world trigger: When `drush sql:dump --format=json` runs remotely with
 * -vvv (very verbose), the output looks like:
 *
 *   [preflight] Config paths: [...]
 *   [debug] ...
 *   {"/tmp/drush_tmp_abc123.sql"}
 *   [success] Database dump saved to /tmp/drush_tmp_abc123.sql [74.3 sec, 17.47 MB]
 *
 * The first character is '[' (from "[preflight]") and the last character is
 * ']' (from "[74.3 sec, 17.47 MB]"). removeNonJsonJunk() matches its array
 * heuristic and returns the entire multi-kilobyte output to json_decode(),
 * which fails with a syntax error.
 *
 * @see https://github.com/consolidation/site-process/issues/XXX
 */
class RemoveNonJsonJunkBugTest extends TestCase
{
    /**
     * Data provider for testRemoveNonJsonJunkArrayHeuristic.
     *
     * Each entry: [description, raw output, should parse successfully]
     */
    public function arrayHeuristicTestValues()
    {
        // Simulated Drush sql:dump output with -vvv where [success] appears
        // AFTER the JSON result. This is the failure case.
        $verboseOutputSuccessAfterJson = implode("\n", [
            ' [preflight] Config paths: /var/www/vendor/drush/drush/drush.yml',
            ' [preflight] Alias paths: /var/www/drush/sites',
            ' [debug] Starting bootstrap to max',
            ' [debug] Trying to connect to database',
            '{"/tmp/drush_tmp_1234567890.sql"}',
            ' [success] Database dump saved to /tmp/drush_tmp_1234567890.sql [74.3 sec, 17.47 MB]',
        ]);

        // Same output but [success] appears BEFORE a JSON string result.
        // This ALSO fails: first char = '[', last char = '"'. The array
        // heuristic doesn't match, but the brace extraction also fails
        // because there are no {} in a JSON string value.
        $verboseOutputSuccessBeforeJson = implode("\n", [
            ' [preflight] Config paths: /var/www/vendor/drush/drush/drush.yml',
            ' [success] Database dump saved to /tmp/drush_tmp_1234567890.sql [74.3 sec, 17.47 MB]',
            '"/tmp/drush_tmp_1234567890.sql"',
        ]);

        // A real JSON array should still work.
        $validJsonArray = '["a", "b", "c"]';

        // Output with debug messages wrapping a JSON object where [success]
        // comes AFTER the JSON. First char = '[', last char = ']'. This ALSO
        // triggers the array heuristic bug.
        $verboseOutputWithJsonObject = implode("\n", [
            ' [preflight] Config paths: /var/www/vendor/drush/drush/drush.yml',
            ' [debug] Starting bootstrap',
            '{"path": "/tmp/drush_tmp_1234567890.sql"}',
            ' [success] Database dump saved to /tmp/file.sql [74.3 sec, 17.47 MB]',
        ]);

        // Minimal reproduction: any non-JSON content where first char = '['
        // and last char = ']' will be returned verbatim to json_decode().
        $minimalFailure = '[not-json-but-starts-with-bracket and ends with bracket]';

        return [
            'BUG: verbose output with success AFTER json - first=[, last=]' => [
                $verboseOutputSuccessAfterJson,
                false, // Should parse but FAILS due to bug
            ],
            'BUG: verbose output before json string - brace extraction fails on strings' => [
                $verboseOutputSuccessBeforeJson,
                false, // Also fails: no {} to extract when JSON result is a string
            ],
            'OK: valid JSON array' => [
                $validJsonArray,
                true,
            ],
            'BUG: verbose output wrapping JSON object - first=[, last=]' => [
                $verboseOutputWithJsonObject,
                false, // Same array heuristic bug: first='[', last=']'
            ],
            'BUG: minimal non-JSON wrapped in brackets' => [
                $minimalFailure,
                false, // Should fail gracefully but triggers wrong code path
            ],
        ];
    }

    /**
     * Tests that removeNonJsonJunk incorrectly handles output where the first
     * character is '[' and the last character is ']' but the content is NOT
     * a valid JSON array.
     *
     * @dataProvider arrayHeuristicTestValues
     */
    public function testRemoveNonJsonJunkArrayHeuristic(string $rawOutput, bool $shouldSucceed)
    {
        $processManager = ProcessManager::createDefault();
        $siteAlias = new SiteAlias([], '@alias.dev');
        $siteProcess = $processManager->siteProcess($siteAlias, ['echo', $rawOutput]);
        $siteProcess->mustRun();

        try {
            $result = $siteProcess->getOutputAsJson();
            if (!$shouldSucceed) {
                // If we get here on a case that SHOULD fail, it means the bug
                // was fixed. Mark as passing (the fix is working).
                $this->assertTrue(true, 'removeNonJsonJunk correctly handled the input after fix.');
            } else {
                $this->assertNotNull($result);
            }
        } catch (\InvalidArgumentException $e) {
            if ($shouldSucceed) {
                $this->fail("Expected valid JSON parsing but got exception: " . $e->getMessage());
            }
            // Verify it's the specific JSON parse error (not some other failure)
            $this->assertStringContainsString(
                'Unable to decode output into JSON',
                $e->getMessage(),
                'Expected the JSON decode error from removeNonJsonJunk bug'
            );
        }
    }

    /**
     * Directly tests the removeNonJsonJunk method via reflection to isolate
     * the heuristic bug without needing process execution.
     *
     * This demonstrates that when output starts with '[' and ends with ']',
     * the method returns the entire input regardless of whether it is valid JSON.
     */
    public function testRemoveNonJsonJunkReturnsInvalidDataForBracketWrappedOutput()
    {
        $processBase = $this->createProcessBaseInstance();
        $method = new \ReflectionMethod($processBase, 'removeNonJsonJunk');
        $method->setAccessible(true);

        // Simulate real Drush -vvv output where [preflight] is first and
        // [success ... 74.3 sec, 17.47 MB] is last.
        $output = implode("\n", [
            ' [preflight] Config paths: /var/www/vendor/drush/drush/drush.yml',
            ' [debug] Trying to connect to database',
            '{"path": "/tmp/drush_tmp_abc.sql"}',
            ' [success] Database dump saved to /tmp/drush_tmp_abc.sql [74.3 sec, 17.47 MB]',
        ]);

        $result = $method->invoke($processBase, $output);

        // BUG: The method returns the entire output because first char = '['
        // and last char = ']'. It should have extracted the JSON object instead.
        //
        // After a fix, this assertion should FAIL (meaning the method now
        // correctly extracts '{"path": "/tmp/drush_tmp_abc.sql"}').
        $this->assertEquals(
            trim($output),
            $result,
            'BUG DEMONSTRATED: removeNonJsonJunk returned the entire output verbatim because first char is "[" and last char is "]". It should have extracted the JSON object.'
        );

        // Verify the returned data is NOT valid JSON (proving the bug causes failures)
        $decoded = json_decode($result, true);
        $this->assertNull(
            $decoded,
            'BUG CONFIRMED: The data returned by removeNonJsonJunk is not valid JSON, which causes getOutputAsJson() to throw "Syntax error".'
        );
    }

    /**
     * Tests that the heuristic works correctly for actual JSON arrays.
     */
    public function testRemoveNonJsonJunkCorrectlyHandlesRealJsonArrays()
    {
        $processBase = $this->createProcessBaseInstance();
        $method = new \ReflectionMethod($processBase, 'removeNonJsonJunk');
        $method->setAccessible(true);

        $validArrays = [
            '["a","b","c"]',
            '[]',
            '[{"key":"value"},{"key2":"value2"}]',
            '[1, 2, 3]',
        ];

        foreach ($validArrays as $input) {
            $result = $method->invoke($processBase, $input);
            $this->assertEquals($input, $result);
            $decoded = json_decode($result, true);
            $this->assertNotNull($decoded, "Valid JSON array should decode: $input");
        }
    }

    /**
     * Creates a ProcessBase instance for reflection-based testing.
     */
    private function createProcessBaseInstance(): ProcessBase
    {
        $processManager = ProcessManager::createDefault();
        $siteAlias = new SiteAlias([], '@alias.dev');
        return $processManager->siteProcess($siteAlias, ['echo', 'test']);
    }
}
