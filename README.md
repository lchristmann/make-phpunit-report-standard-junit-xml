# Make PHPUnit Report standard Junit XML

PHPUnit's JUnit test reports often cause problems, because they deviate from the standard, mainly in two ways:

- nested `<testsuite>` elements
- calling the `classname` property on `<testcase>` elements "`class`" instead

I've marked those issues in the `phpunit-junit-xml-report.xml` with TODO comments.

My personal use case is BrowserStack Test Observability failing to process my XML report from PHPUnit.

But there's also been a [GitLab issue in 2018](https://gitlab.com/gitlab-org/gitlab-foss/-/issues/50959) for this problem.

## A simple solution

Strategy:

- transform the PHPUnit Junit XML report (`phpunit-junit-xml-report.xml`)
- into a standard Junit XML report (`phpunit-junit-xml-report-target.xml`)
  - I use [Sample JUnit report with explanatory comments for that provided by BrowserStack Test Observability](https://www.browserstack.com/docs/test-observability/references/upload-junit-reports#supported-xml-schemas) (`browserstack-junit-xml-report-sample.xml`)
- with a PHP script (`fix-phpunit-junit-xml-report.php`)

Implementation:

- use [PHP XML DOM Parser](https://www.w3schools.com/php/php_xml_dom.asp) - an easy way to process XML documents in PHP
- have input and output file path configurable as command line arguments

## The desired result

A standard JUnit XML report is desired, complying to the mentioned JUnit XML reference from BrowserStack (`browserstack-junit-xml-report-sample.xml`).

I've specified this in `phpunit-junit-xml-report-target.xml`. This is what the PHP script will make out of the given input (`phpunit-junit-xml-report.xml`).

## Quickly test it out

No need to install anything - just run this PHP script in a docker container.

Linux:

```shell
docker run --rm --volume "$PWD":/app --workdir /app php:8.2-cli bash -c "
    php fix-phpunit-junit-xml-report.php phpunit-junit-xml-report.xml fixed-junit.xml"
```

Windows: (adapt path to project!)

```shell
docker run --rm --volume C:\path\to\project:/app --workdir /app php:8.2-cli php fix-phpunit-junit-xml-report.php phpunit-junit-xml-report.xml fixed-junit.xml
```

- `--rm` makes it that the container is removed after it's done (the `fixed-junit.xml` will still be there though, beucase...)
- ...`--volume` syncs your current working directory with the containers app directory (be sure you're in this project's directory here with your shell!)
- `--workdir /app` sets the container's working directory (it's where we mounted our volume, so this project's files are there!)
- `bash -c "..."` [executes the given string as a command](https://www.reddit.com/r/docker/comments/10ng7hd/what_is_the_purpose_of_bash_c/)
  - note that we could execute more commands by chaining them: `command1 && command2`