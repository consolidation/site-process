# SiteProcess

Put a one-line description of your project here.

[![Travis CI](https://travis-ci.org/consolidation/site-process.svg?branch=master)](https://travis-ci.org/consolidation/site-process)
[![Windows CI](https://ci.appveyor.com/api/projects/status/{{PUT_APPVEYOR_STATUS_BADGE_ID_HERE}}?svg=true)](https://ci.appveyor.com/project/consolidation/site-process)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/consolidation/site-process/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/consolidation/site-process/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/consolidation/site-process/badge.svg?branch=master)](https://coveralls.io/github/consolidation/site-process?branch=master) 
[![License](https://img.shields.io/badge/license-MIT-408677.svg)](LICENSE)


## Overview

Site Process is a thin wrapper around the [Symfony Process Component](https://symfony.com/doc/3.4/components/process) that allows applications to use the [Site Alias library](https://github.com/consolidation/site-alias) to specify the target for a remote call.

For comparison purposes, the `Process` obejct may be created to run an application on the local system using the standard Symfony Process Component API like so:
```
$process = new Process(['ls', '-lsa']);
```
Similarly, a remote call can be done using the general-purpose `SiteProcess` API:
```
$process = new SiteProcess($site_alias, ['ls', '-lsa', '{root}']);
```
In this example, if `$site_alias` represents a site on the same system, then the `ls -lsa` command will run locally. If, on the other hand, it represents a remote site, then the `ls -lsa` command will be wrapped in an ssh call to the remote system. In either case, the `{root}` reference will be replaced with the value of the attribute of the site alias named `root`. An exception will be thrown if the named attribute does not exist.

Options may also be specified as an associative array provided as a third parameter:
```
$process = new SiteProcess($site_alias, ['git', 'status'], ['untracked-files' => 'no']);
```
This is equivalent to:
```
$process = new SiteProcess($site_alias, ['git', '--untracked-files=no', 'status']);
```

### Release Script

To automatically attach a .phar (or some other build result) to every GitHub release:

- Run `travis setup releases` and answer the prompts. Answer "yes" to all yes/no questions.
- Edit resulting `.travis.yml` file to include the missing `skip_cleanup:` and `tags:` directives.

When you are done, the `deploy:` section should look something like this:

```
deploy:
  provider: releases
  api_key:
    secure: KmKwmt...[REDACTED]...LlE=
  file: example.phar
  skip_cleanup: true
  on:
    tags: true
    repo: consolidation/site-process
```

The `secure:` line should be filled in by `travis setup releases`.



## Running the tests

The test suite may be run locally by way of some simple composer scripts:

| Test             | Command
| ---------------- | ---
| Run all tests    | `composer test`
| PHPUnit tests    | `composer unit`
| PHP linter       | `composer lint`
| Code style       | `composer cs`     
| Fix style errors | `composer cbf`


## Deployment

Add additional notes about how to deploy this on a live system.

If your project has been set up to automatically deploy its .phar with every GitHub release, then you will be able to deploy by the following procedure:

- Edit the `VERSION` file to contain the version to release with `-dev` appended, and commit the change.
- Run `composer release`

## Built With

List significant dependencies that developers of this project will interact with.

* [Composer](https://getcomposer.org/) - Dependency Management
* [Robo](https://robo.li/) - PHP Task Runner
* [Symfony](https://symfony.com/) - PHP Framework

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [releases](https://github.com/consolidation/site-process/releases) page.

## Authors

* **Greg Anderson** - created project from template.

See also the list of [contributors](https://github.com/consolidation/site-process/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details

## Acknowledgments

* Hat tip to anyone who's code was used
* Inspiration
* etc
* Thanks to PurpleBooth for the [example README template](https://gist.github.com/PurpleBooth/109311bb0361f32d87a2)
