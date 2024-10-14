#!/usr/bin/env php
<?php

function ask(string $question, string $default = ''): string
{
    $answer = readline($question . ($default ? " ({$default})" : null) . ': ');

    if (!$answer) {
        return $default;
    }

    return $answer;
}

function confirm(string $question, bool $default = false): bool
{
    $answer = ask($question . ' (' . ($default ? 'Y/n' : 'y/N') . ')');

    if (!$answer) {
        return $default;
    }

    return strtolower($answer) === 'y';
}

function prompt_select(string $prompt, array $options): string
{
    $selectOptions = range(1, count($options));

    $displayOptions = array_combine($selectOptions, $options);

    while (true) {

        foreach ($displayOptions as $select => $option) {
            print "$select) $option".PHP_EOL;
        }

        $keystroke = ask($prompt);

        if (in_array($keystroke, array_keys($displayOptions))) {
            return array_search($displayOptions[$keystroke], $options);
        }
    }
}

function writeln(string $line): void
{
    echo $line . PHP_EOL;
}

function run(string $command): string
{
    return trim((string) shell_exec($command));
}

function str_after(string $subject, string $search): string
{
    $pos = strrpos($subject, $search);

    if ($pos === false) {
        return $subject;
    }

    return substr($subject, $pos + strlen($search));
}

function slugify(string $subject): string
{
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $subject), '-'));
}

function title(string $subject): string
{
    return ucwords(str_replace(['-', '_'], ' ', $subject));
}

function title_case(string $subject): string
{
    return str_replace(' ', '', title($subject));
}

function title_snake(string $subject, string $replace = '_'): string
{
    return str_replace(['-', '_'], $replace, $subject);
}

function replace_in_file(string $file, array $replacements): void
{
    $contents = file_get_contents($file);

    file_put_contents(
        $file,
        str_replace(
            array_keys($replacements),
            array_values($replacements),
            $contents
        )
    );
}

function remove_prefix(string $prefix, string $content): string
{
    if (str_starts_with($content, $prefix)) {
        return substr($content, strlen($prefix));
    }

    return $content;
}

function remove_composer_deps(array $names)
{
    $data = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);

    foreach ($data['require-dev'] as $name => $version) {
        if (in_array($name, $names, true)) {
            unset($data['require-dev'][$name]);
        }
    }

    file_put_contents(__DIR__ . '/composer.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function remove_composer_script($scriptName)
{
    $data = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);

    foreach ($data['scripts'] as $name => $script) {
        if ($scriptName === $name) {
            unset($data['scripts'][$name]);
            break;
        }
    }

    file_put_contents(__DIR__ . '/composer.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function remove_readme_paragraphs(string $file): void
{
    $contents = file_get_contents($file);

    file_put_contents(
        $file,
        preg_replace('/<!--delete-->.*<!--\/delete-->/s', '', $contents) ?: $contents
    );
}

function safeUnlink(string $filename)
{
    if (file_exists($filename) && is_file($filename)) {
        unlink($filename);
    }
}

function determineSeparator(string $path): string
{
    return str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function replaceForWindows(): array
{
    return preg_split('/\\r\\n|\\r|\\n/', run('dir /S /B * | findstr /v /i .git\ | findstr /v /i vendor | findstr /v /i ' . basename(__FILE__) . ' | findstr /r /i /M /F:/ ":author :vendor :package VendorName skeleton vendor_name vendor_slug author@domain.com"'));
}

function replaceForAllOtherOSes(): array
{
    return explode(PHP_EOL, run('grep -E -r -l -i ":author|:vendor|:package|VendorName|skeleton|vendor_name|vendor_slug|author@domain.com" --exclude-dir=vendor | grep -v ' . basename(__FILE__)));
}

function getGitHubApiEndpoint(string $endpoint): ?stdClass
{
    try {
        $curl = curl_init("https://api.github.com/{$endpoint}");
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: spatie-configure-script/1.0',
            ],
        ]);

        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($statusCode === 200) {
            return json_decode($response);
        }
    } catch (Exception $e) {
        // ignore
    }

    return null;
}

function searchCommitsForGitHubUsername(): string
{
    $authorName = strtolower(trim(shell_exec('git config user.name')));

    $committersRaw = shell_exec("git log --author='@users.noreply.github.com' --pretty='%an:%ae' --reverse");
    $committersLines = explode("\n", $committersRaw ?? '');
    $committers = array_filter(array_map(function ($line) use ($authorName) {
        $line = trim($line);
        [$name, $email] = explode(':', $line) + [null, null];

        return [
            'name' => $name,
            'email' => $email,
            'isMatch' => strtolower($name) === $authorName && !str_contains($name, '[bot]'),
        ];
    }, $committersLines), fn ($item) => $item['isMatch']);

    if (empty($committers)) {
        return '';
    }

    $firstCommitter = reset($committers);

    return explode('@', $firstCommitter['email'])[0] ?? '';
}

function guessGitHubUsernameUsingCli()
{
    try {
        if (preg_match('/ogged in to github\.com as ([a-zA-Z-_]+).+/', shell_exec('gh auth status -h github.com 2>&1'), $matches)) {
            return $matches[1];
        }
    } catch (Exception $e) {
        // ignore
    }

    return '';
}

function guessGitHubUsername(): string
{
    $username = searchCommitsForGitHubUsername();
    if (!empty($username)) {
        return $username;
    }

    $username = guessGitHubUsernameUsingCli();
    if (!empty($username)) {
        return $username;
    }

    // fall back to using the username from the git remote
    $remoteUrl = shell_exec('git config remote.origin.url');
    $remoteUrlParts = explode('/', str_replace(':', '/', trim($remoteUrl)));

    return $remoteUrlParts[1] ?? '';
}

function guessGitHubVendorInfo($authorName, $username): array
{
    $remoteUrl = shell_exec('git config remote.origin.url');
    $remoteUrlParts = explode('/', str_replace(':', '/', trim($remoteUrl)));

    $response = getGitHubApiEndpoint("orgs/{$remoteUrlParts[1]}");

    if ($response === null) {
        return [$authorName, $username];
    }

    return [$response->name ?? $authorName, $response->login ?? $username];
}

$gitName = run('git config user.name');
$authorName = ask('Author name', $gitName);

$gitEmail = run('git config user.email');
$authorEmail = ask('Author email', $gitEmail);
$authorUsername = ask('Author username', guessGitHubUsername());
$authorCompany = ask('Author company');
$authorCopyriqhtYear = ask('Author copyright year');

$guessGitHubVendorInfo = guessGitHubVendorInfo($authorName, $authorUsername);

$vendorName = ask('Vendor name', $guessGitHubVendorInfo[0]);
$vendorUsername = ask('Vendor username', $guessGitHubVendorInfo[1] ?? slugify($vendorName));
$vendorSlug = slugify($vendorUsername);

$vendorNamespace = str_replace('-', '', ucwords($vendorName));
$vendorNamespace = ask('Vendor namespace', $vendorNamespace);

$currentDirectory = getcwd();
$folderName = basename($currentDirectory);

$packageName = ask('Package name', $folderName);
$packageSlug = slugify($packageName);
$packageSlugWithoutPrefix = remove_prefix('prestashop-', $packageSlug);

$className = title_case($packageSlugWithoutPrefix);
$className = ask('Class name', $className);
$variableName = lcfirst($className);

$moduleName = title($packageSlugWithoutPrefix);
$moduleFilename = strtolower($className);

$prompt = "Select the module category";
$moduleCategories = [
    'administration' => 'Administration',
    'advertising_marketing' => 'Advertising & Marketing',
    'analytics_stats' => 'Analytics & Stats',
    'checkout' => 'Checkout',
    'smart_shopping' => 'Comparison site & Feed management',
    'content_management' => 'Content Management',
    'customer_reviews' => 'Customer Reviews',
    'dashboard' => 'Dashboard',
    'emailing' => 'E-mailing',
    'export' => 'Export',
    'front_office_features' => 'Front Office Features',
    'i18n_localization' => 'Internationalization & Localization',
    'market_place' => 'Marketplace',
    'merchandizing' => 'Merchandizing',
    'migration_tools' => 'Migration Tools',
    'mobile' => 'Mobile',
    'others' => 'Other Modules',
    'payments_gateways' => 'Payments & Gateways',
    'pricing_promotion' => 'Pricing & Promotion',
    'quick_bulk_update' => 'Quick / Bulk update',
    'search_filter' => 'Search & Filter',
    'seo' => 'SEO',
    'shipping_logistics' => 'Shipping & Logistics',
    'payment_security' => 'Site certification & Fraud prevention',
    'slideshows' => 'Slideshows',
    'social_community' => 'Social & Community',
    'social_networks' => 'Social Networks',
    'billing_invoicing' => 'Taxes & Invoices',
];

$moduleCategory = prompt_select($prompt, $moduleCategories);

$description = ask('Package description', "This is my package {$packageSlug}");

if ($moduleCategory === 'payments_gateways') {
    unlink(__DIR__ . '/skeletonModule.php');
} else {
    unlink(__DIR__ . '/skeletonPaymentModule.php');
}

writeln('------');
writeln("Author     : {$authorName} ({$authorUsername}, {$authorEmail})");
writeln("Vendor     : {$vendorName} ({$vendorSlug})");
writeln("Package    : {$packageSlug} <{$description}>");
writeln("Namespace  : {$vendorNamespace}\\{$className}");
writeln("Class name : {$className}");
writeln("Module name : {$moduleName}");
writeln("Module category : {$moduleCategories[$moduleCategory]}");
writeln("Module description : {$description}");
writeln("Copyright : Since {$authorCopyriqhtYear} {$authorCompany}");
writeln('------');

writeln('This script will replace the above values in all relevant files in the project directory.');

if (!confirm('Modify files?', true)) {
    exit(1);
}

$files = (str_starts_with(strtoupper(PHP_OS), 'WIN') ? replaceForWindows() : replaceForAllOtherOSes());

foreach ($files as $file) {
    replace_in_file($file, [
        ':author_name' => $authorName,
        ':author_username' => $authorUsername,
        'author@domain.com' => $authorEmail,
        ':author_company' => $authorCompany,
        ':author_copyright_year' => $authorCopyriqhtYear,
        ':vendor_name' => $vendorName,
        ':vendor_slug' => $vendorSlug,
        'VendorName' => $vendorNamespace,
        ':package_name' => $packageName,
        ':package_slug' => $packageSlug,
        ':package_slug_without_prefix' => $packageSlugWithoutPrefix,
        '{{SKELETON}}' => strtoupper($className),
        'SkeletonModule' => $className,
        'SkeletonPaymentModule' => $className,
        'Skeleton' => $className,
        'skeleton' => $packageSlug,
        ':module_name' => $moduleName,
        ':module_filename' => $moduleFilename,
        ':module_category' => $moduleCategory,
        ':translation_key' => ucfirst($moduleFilename),
        'variable' => $variableName,
        ':package_description' => $description,
    ]);

    match (true) {
        str_contains($file, determineSeparator('skeletonModule.php')) => rename($file, determineSeparator('./' . $moduleFilename . '.php')),
        str_contains($file, determineSeparator('skeletonPaymentModule.php')) => rename($file, determineSeparator('./' . $moduleFilename . '.php')),
        str_contains($file, 'README.md') => remove_readme_paragraphs($file),
        default => [],
    };
}

confirm('Execute `composer install`?')
&& run('composer install');

unlink(__FILE__);
