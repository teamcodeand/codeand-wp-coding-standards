# Code& WP Coding Standards

- [Installation](#installation)
- [Setup](#setup)
- [Usage](#usage)
  - [Use in an IDE](#use-within-your-IDE)
- [Developing this standard](#developing-this-standard)
- [Credits](#credits)

Code& Coding Standards is a project with rulesets for code style and quality tools to be used in Code& WordPress projects.

It is a mix with [PSR-1](https://www.php-fig.org/psr/psr-1/), [PSR-2](https://www.php-fig.org/psr/psr-2), [PSR-4](https://www.php-fig.org/psr/psr-4/), [PSR-12](https://www.php-fig.org/psr/psr-12/) and [WordPress Coding Standards](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards).

Whenever there's a conflict between PSR and WPCS, we almost always prefer PSR.

## Installation

PHP 8.2 or later is required to install and run these coding standards.

Merge this repository and plugin permission into the WordPress project's `composer.json`, preserving its existing entries:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:teamcodeand/codeand-wp-coding-standards.git",
      "no-api": true
    }
  ],
  "config": {
    "allow-plugins": {
      "dealerdirect/phpcodesniffer-composer-installer": true
    }
  }
}
```

From the project root, install the standard and the plugin that registers its rules with PHP_CodeSniffer:

```sh
composer require --dev teamcodeand/codeand-wp-coding-standards:dev-main dealerdirect/phpcodesniffer-composer-installer:^1.2
```

Verify registration with `vendor/bin/phpcs -i`. The list should include `codeand`, `WordPress`, `PHPCompatibilityWP`, `VariableAnalysis`, `PSR2R` and `SlevomatCodingStandard`. Let the Composer plugin manage `installed_paths`; setting it to only the Code& directory hides the other required standards.

## Setup

First, create [`phpcs.xml`](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Annotated-Ruleset) on project root:

```xml
<?xml version="1.0"?>
<ruleset name="codeandbedrock">
    <!-- Check all files under app/ (assuming Bedrock) -->
    <file>./web/app/</file>

    <!-- Show colors in console -->
    <arg value="-colors"/>

    <!-- Show progress and sniff codes in all reports; Show progress of the run -->
    <arg value="sp"/>

    <!-- Scan only PHP files -->
    <arg name="extensions" value="php"/>

    <exclude-pattern>vendor/</exclude-pattern>

    <!-- TODO: Probably change everything below! -->
    <!-- TODO: Exclude specific rules if necessary -->

    <!-- Use Code& WP Coding Standards -->
    <rule ref="codeand">
        <!-- And only inspect custom stuffs -->
        <include-pattern>/web/app/plugins/myplugin/*</include-pattern>
        <include-pattern>/web/app/themes/childtheme/*</include-pattern>
    </rule>

    <!-- TODO: Define minimum supported WordPress version -->
    <config name="minimum_supported_wp_version" value="5.2"/>

    <!-- TODO: Define expected text domains -->
    <rule ref="WordPress.WP.I18n">
        <properties>
            <property name="text_domain" type="array">
                <element value="my-plugin"/>
                <element value="my-theme"/>
                <element value="woocommerce"/>
                <element value="sage"/>
            </property>
        </properties>
    </rule>
</ruleset>
```

For Bedrock, adjust the include patterns to cover the project's own plugins, themes and MU plugins. The `<file>` entries determine which paths PHPCS searches; the include patterns determine where the rules apply.

For a standalone plugin, use `<file>./</file>` and `<rule ref="codeand"/>` instead of the Bedrock file entry and scoped `codeand` rule above. Keep the `vendor/` exclusion and set the plugin's WordPress minimum and text domain.

## Usage
Either define the project's [composer scripts](https://getcomposer.org/doc/articles/scripts.md):

```json
{
  "scripts": {
    "test": [
      "phpcs"
    ],
    "fix": [
      "phpcbf"
    ]
  }
}
```

Run the commands:

```sh-session
$ composer test
$ composer fix
```

### Use within your IDE
Or alternatively, set this up in your IDE to check your code as you're working on it. You'll probably find a guide for how to do that in the WPCS Readme under [Using PHPCS and WPCS from within your IDE](https://github.com/WordPress/WordPress-Coding-Standards#using-phpcs-and-wpcs-from-within-your-ide)

## Developing this standard

From a checkout of this repository, run `composer install` followed by `composer test` using PHP 8.2 or later. The regression checks cover naming, imports, variable analysis, spacing and project file scope. GitHub Actions runs them on PHP 8.2 for non-draft pull requests targeting `develop`, `master` or `main`, and for pushes to `main`. Draft PRs are skipped; marking a PR ready for review triggers the checks.

## Credits

Largely based on [Itineris WP Coding Standards](https://github.com/ItinerisLtd/itineris-wp-coding-standards) created by [Tang Rufus](https://typist.tech). Also makes use of some rules from [Human Made](https://github.com/humanmade/coding-standards) with a smattering of input from Code&.
