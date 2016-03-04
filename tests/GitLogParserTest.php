<?php

use JamesBrowne\GitLog\GitLogParser;

class GitLogParserTest extends PHPUnit_Framework_TestCase
{
    public function testTitleAndBodyAreExtracted()
    {
        $log = new GitLogParser(fopen(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'resources/title_and_body.txt', 'r'));
        $logs = $log->toArray();

        $this->assertObjectHasAttribute('Title', $logs[0]);
        $this->assertObjectHasAttribute('Body', $logs[0]);

        $this->assertObjectHasAttribute('Title', $logs[1]);
        $this->assertObjectHasAttribute('Body', $logs[1]);

    }

    public function testCorrectNumberOfCommits()
    {
        $log = new GitLogParser(fopen(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'resources/five_commits.txt', 'r'));
        $logs = $log->toArray();

        $this->assertEquals(5, count($logs));
    }

    public function testMultilineBodyFormattedCorrectly()
    {
        $log = new GitLogParser(fopen(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'resources/multiline_body.txt', 'r'));
        $logs = $log->toArray();

        $expected_text = <<<EOT
> Lists of implements MAY be split across multiple lines, where each subsequent line is indented once. When doing so, the first item in the list MUST be on the next line, and there MUST be only one interface per line.

https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md#41-extends-and-implements
EOT;

        $this->assertEquals($expected_text, $logs[0]->Body);
    }
}

