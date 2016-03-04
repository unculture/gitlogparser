GitLogParser
---

For tests (not comprehensive!), run

```
composer install
./vendor/bin phpunit
```

To echo JSON from git log out put, pipe the output to main.php, eg.

```
git log --pretty=fuller | path/to/main.php | less
```
or

```
git log | path/to/main.php | less
```

It works with the default, short, medium, full and fuller log output formats.
