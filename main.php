<?php
require_once 'vendor/autoload.php';

use JamesBrowne\GitLog\GitLogParser;

$log = new GitLogParser(STDIN);

echo json_encode($log->toArray(), JSON_PRETTY_PRINT);

