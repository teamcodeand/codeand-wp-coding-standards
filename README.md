# Code& WP Coding Standards

- [Installation](#installation)
- [Setup](#setup)
- [Usage](#usage)
  - [Use in an IDE](#use-within-your-IDE)
- [Credits](#credits)

Code& Coding Standards is a project with rulesets for code style and quality tools to be used in Code& WordPress projects.

It is a mix with [PSR-1](https://www.php-fig.org/psr/psr-1/), [PSR-2](https://www.php-fig.org/psr/psr-2), [PSR-4](https://www.php-fig.org/psr/psr-4/), [PSR-12](https://www.php-fig.org/psr/psr-12/) and [WordPress Coding Standards](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards).

Whenever there's a conflict between PSR and WPCS, we almost always prefer PSR.

## Installation

Add this repository to the `composer.json` for the WP project and add to `require-dev`:

```json
# composer.json
"repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:teamcodeand/codeand-wp-coding-standards.git",
      "no-api": true
	}
],
"require-dev": {
	"teamcodeand/codeand-wp-coding-standards": "^0.1.0"
}
```

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

    <!-- Install custom rulesets -->
    <config name="installed_paths" value="vendor/wp-coding-standards/wpcs,vendor/codeand/codeand-wp-coding-standards"/>

    <!-- Use Code& WP Coding Standards -->
    <rule ref="codeand"/>

    <!-- TODO: Probably change everything below! -->
    <!-- TODO: Exclude specific rules if necessary -->

    <!-- Bedrock: Exclude everything -->
    <exclude-pattern>/web/app/languages/*</exclude-pattern>
    <exclude-pattern>/web/app/mu-plugins/*</exclude-pattern>
    <exclude-pattern>/web/app/plugins/*</exclude-pattern>
    <exclude-pattern>/web/app/themes/*</exclude-pattern>
    <exclude-pattern>/web/app/uploads/*</exclude-pattern>

    <!-- But inspect custom stuffs -->
    <include-pattern>/web/app/plugins/myplugin/*</include-pattern>
    <include-pattern>/web/app/themes/childtheme/*</include-pattern>

    <!-- TODO: Define minimum supported WordPress version -->
    <config name="minimum_supported_wp_version" value="5.2"/>

    <!-- TODO: Define expected text domains -->
    <rule ref="WordPress.WP.I18n">
        <properties>
            <property name="text_domain" type="array" value="my-plugin,my-theme,woocommerce,sage"/>
        </properties>
    </rule>
</ruleset>
```
## Usage
Either define the project's [composer scripts](https://getcomposer.org/doc/articles/scripts.md):

```json
# composer.json
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

## Credits

Largely based on [Itineris WP Coding Standards](https://github.com/ItinerisLtd/itineris-wp-coding-standards) created by [Tang Rufus](https://typist.tech). Also makes use of some rules from [Human Made](https://github.com/humanmade/coding-standards) with a smattering of input from Code&.
