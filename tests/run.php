<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$temporary = sys_get_temp_dir() . '/codeand-cs-' . bin2hex(random_bytes(8));
mkdir($temporary);
$temporary = realpath($temporary);
$checks = 0;

function check(bool $condition, string $description): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($description);
    }
    ++$checks;
}

function scan(string $standard, string $path): array
{
    global $root, $temporary;
    $process = proc_open(
        [PHP_BINARY, $root . '/vendor/bin/phpcs', '--standard=' . $standard, '--report=json', '--no-colors', $path],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $temporary
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start PHPCS.');
    }
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    $report = json_decode($output, true);
    if ($status > 3 || !isset($report['files'], $report['totals'])) {
        throw new RuntimeException("PHPCS could not load or run the standard:\n$output\n$errors");
    }
    return $report['files'];
}

function sample(string $code): array
{
    global $root, $temporary;
    $path = $temporary . '/sample.php';
    file_put_contents($path, "<?php\n" . $code . "\n");
    $files = scan($root . '/codeand/ruleset.xml', $path);
    return $files[$path]['messages'];
}

function sources(array $messages, array $prefixes): array
{
    $matches = [];
    foreach ($messages as $message) {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($message['source'], $prefix)) {
                $matches[] = $message['source'];
                break;
            }
        }
    }
    sort($matches);
    return $matches;
}

try {
    foreach (['class', 'interface', 'trait', 'enum'] as $kind) {
        foreach (['ReviewService', 'Codeand_Review', 'WP_Review', 'reviewService', 'Codeand_review', 'Codeand__Review'] as $name) {
            $messages = sample("$kind $name\n{\n}\n");
            $errors = sources($messages, ['Squiz.Classes.ValidClassName.', 'PEAR.NamingConventions.ValidClassName.']);
            $valid = in_array($name, ['ReviewService', 'Codeand_Review', 'WP_Review'], true);
            check(($errors === []) === $valid, "Unexpected naming result for $kind $name: " . json_encode($errors));
        }
    }

    $messages = sample('class Codeand_Review { public function snake_case() { return true; } }');
    check(sources($messages, ['PSR1.Methods.CamelCapsMethodName.']) === [], 'snake_case methods must remain allowed.');

    foreach (['use Vendor\Alpha;\nuse Vendor\Beta;', 'use Vendor\{Alpha, Beta};', 'use Vendor\Alpha, Vendor\Beta;'] as $index => $imports) {
        $messages = sample(str_replace('\\n', "\n", $imports) . "\n\nnew Alpha();\nnew Beta();");
        $errors = sources($messages, ['PSR2.Namespaces.UseDeclaration.MultipleDeclarations']);
        check(count($errors) === ($index === 0 ? 0 : 1), 'Imports must use separate use statements.');
    }

    $messages = sample('function codeand_demo( $used, $unused ) { return $used; }');
    $errors = sources($messages, ['Generic.CodeAnalysis.UnusedFunctionParameter.', 'VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable']);
    check($errors === ['VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable'], 'An unused parameter must produce exactly one warning.');

    $messages = sample('function codeand_demo() { $unused = 1; return $undefined; }');
    $errors = sources($messages, ['VariableAnalysis.']);
    check($errors === [
        'VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable',
        'VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable',
    ], 'Unused and undefined local variables must still be detected.');

    $messages = sample("function codeand_demo( \$flag ) {\n    if( \$flag ) {\n        return true;\n    }\n}");
    $errors = sources($messages, ['Squiz.ControlStructures.ControlSignature.', 'WordPress.WhiteSpace.ControlStructureSpacing.']);
    check($errors === ['Squiz.ControlStructures.ControlSignature.SpaceAfterKeyword'], 'A missing space after if must produce exactly one spacing error.');

    $messages = sample("function codeand_demo( \$flag ) {\n    if (\$flag) {\n        return true;\n    }\n}");
    $errors = sources($messages, ['WordPress.WhiteSpace.ControlStructureSpacing.']);
    check($errors === [
        'WordPress.WhiteSpace.ControlStructureSpacing.NoSpaceAfterOpenParenthesis',
        'WordPress.WhiteSpace.ControlStructureSpacing.NoSpaceBeforeCloseParenthesis',
    ], 'Spaces inside control-structure parentheses must remain required.');

    $project = $temporary . '/project';
    foreach (['owned', 'third-party', 'config'] as $directory) {
        mkdir($project . '/' . $directory, 0777, true);
        file_put_contents($project . '/' . $directory . '/sample.php', "<?php\ncreate_function('', '');\n");
    }
    $standard = htmlspecialchars($root . '/codeand/ruleset.xml', ENT_QUOTES | ENT_XML1);
    $rules = '<ruleset name="Scope"><rule ref="' . $standard . '"><include-pattern>/owned/</include-pattern></rule>';
    $rulesPath = $temporary . '/scope.xml';
    file_put_contents($rulesPath, $rules . '</ruleset>');
    $files = scan($rulesPath, $project);
    check(count($files) === 3, 'The scope fixture must discover all three files.');
    check(sources($files[$project . '/owned/sample.php']['messages'], ['PHPCompatibility.']) !== [], 'Owned code must be checked for PHP compatibility.');
    check($files[$project . '/third-party/sample.php']['messages'] === [], 'Rules must not leak into third-party code.');
    check($files[$project . '/config/sample.php']['messages'] === [], 'Shared standards must not select config by default.');

    $compatibility = '<rule ref="PHPCompatibilityWP"><include-pattern>/owned/</include-pattern><include-pattern>/config/</include-pattern></rule>';
    file_put_contents($rulesPath, str_replace('<ruleset name="Scope">', '<ruleset name="Scope">' . $compatibility, $rules) . '</ruleset>');
    $files = scan($rulesPath, $project);
    check(sources($files[$project . '/owned/sample.php']['messages'], ['PHPCompatibility.']) !== [], 'Adding config coverage must retain compatibility checks for owned code.');
    $messages = $files[$project . '/config/sample.php']['messages'];
    check($messages !== [] && count(sources($messages, ['PHPCompatibility.'])) === count($messages), 'Projects must be able to opt config into compatibility checks only: ' . json_encode($messages));
    check($files[$project . '/third-party/sample.php']['messages'] === [], 'Adding config coverage must not select third-party code.');

    echo "Passed $checks regression checks.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    $failed = true;
} finally {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($temporary);
}

exit(isset($failed) ? 1 : 0);
