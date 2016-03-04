<?php

namespace JamesBrowne\GitLog;

/**
 * Class GitLogParser
 * @package JamesBrowne\GitLog
 * Parses out a JSON object, or an array, of commit objects from a git log.
 * Works with git log standard formats short, medium, full and fuller
 * These have general format as follows:
 * commit xxx
 * attr: value
 * attr: value
 * blank line
 * title
 * blank line
 * multiline commit message body
 * blank line
 *
 * May be constructed with either a stream (from STDIN) or an open file handle
 */
class GitLogParser
{
    /**
     * Constants for representing which type of line we have
     */
    const COMMIT_START = 1;
    const ATTRIBUTE = 2;
    const BLANK_LINE = 3;
    const TEXT_LINE = 4;

    /**
     * The commits parsed from the input
     * @var array
     */
    private $commits = [];

    /**
     * GitLogParser constructor.
     * Must be constructed with a stream or a handle to a file resource
     * @param resource $resource
     */
    public function __construct($resource)
    {
        if (get_resource_type($resource) !== 'stream' && get_resource_type($resource) !== 'file') {
            throw new \InvalidArgumentException("GitLog requires constructing with a handle to a file or a stream");
        }

        $this->parseCommitLog($resource);
    }

    /**
     * Get the commits as JSON
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->commits);
    }

    /**
     * Get the commits as an array
     * @return array
     */
    public function toArray()
    {
        return $this->commits;
    }

    /**
     * Given the resource, parse out the commits
     * @param $resource
     */
    private function parseCommitLog($resource)
    {
        $commit = new \stdClass;

        while ($line = fgets($resource)) {
            // Switch on the type of line we've matched
            // Note that the handle methods mutate the current commit
            switch($this->matchLine($line)) {
                case self::COMMIT_START:
                    $commit = $this->handleCommitStartLine($commit, $line);
                    break;
                case self::ATTRIBUTE:
                    $this->handleAttributeLine($line, $commit);
                    break;
                case self::TEXT_LINE:
                    $this->handleTextLine($line, $commit);
                    break;
                case self::BLANK_LINE:
                    $this->handleBlankLine($commit);
                    break;
                default:
            }
        }
        // Save final commit
        $this->savePreviousCommit($commit);
    }

    /**
     * Which type of line do we have?
     * @param $line string the current line being read from the resource
     * @return int
     */
    private function matchLine($line) {
        if (preg_match('/^commit .*$/u', $line)) {
            return self::COMMIT_START;
        }
        if (preg_match('/^\S+.*:.*$/u', $line)) {
            return self::ATTRIBUTE;
        }
        if (preg_match('/^\s*$/u', $line)) {
            return self::BLANK_LINE;
        }
        if (preg_match('/^.*\w*.*$/u', $line)) {
            return self::TEXT_LINE;
        }
    }

    /**
     * Save the commit, if there is one, into the list
     * @param $commit \stdClass The object representing the commit
     */
    private function savePreviousCommit($commit)
    {
        if ($commit && property_exists($commit, 'Hash')) {
            $this->commits[] = $commit;
        }
    }

    /**
     * Deal with lines that might be the commit title or body
     * @param $line string The current line
     * @param $commit \stdClass The object representing the commit
     */
    private function handleTextLine($line, $commit)
    {
        // Trim the line
        $text = preg_replace('/(^\s+)|(\s*$)/u', "", $line);

        // If we have a title already, it must be part of the body
        if (property_exists($commit, 'Title')) {
            if (!property_exists($commit, 'Body')) {
                $commit->Body = '';
            }
            if (empty($commit->Body)) {
                $commit->Body = $text;
            } else {
                $commit->Body .= PHP_EOL . $text;
            }
        } else {
            $commit->Title = $text;
        }
    }

    /**
     * Handle blank lines
     * @param $commit \stdClass The object representing the commit
     */
    private function handleBlankLine($commit)
    {
        // If there's a body already, this blank line is probably intentional, let's leave it in
        if (property_exists($commit, "Body")) {
            $commit->Body .= PHP_EOL;
        }
    }

    /**
     * Deal with attribute lines, eg. Author, Date etc
     * @param $line string The current line
     * @param $commit \stdClass The object representing the commit
     */
    private function handleAttributeLine($line, $commit)
    {
        // Extract attribute name, replace any non word characters with underscores
        // NB: might end up with numbers at the beginning which would be invalid
        $attribute_name = preg_replace('/\W+/u', '_', preg_replace('/(:.*$)|\s*$/u', '', $line));
        $commit->{$attribute_name} = preg_replace('/\s*$/u', '', preg_replace('/^[^:]*:\s*/u', '', $line));
    }

    /**
     * Handle the line that starts a new commit
     * @param $line string The current line
     * @param $commit \stdClass The object representing the commit
     * @return \stdClass
     */
    private function handleCommitStartLine($commit, $line)
    {
        $this->savePreviousCommit($commit);
        $commit = new \stdClass;
        $commit->Hash = preg_replace('/(^commit )|\s*$/u', "", $line);
        return $commit;
    }
}