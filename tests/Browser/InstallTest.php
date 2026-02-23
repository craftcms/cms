<?php

declare(strict_types=1);

beforeEach(function () {
    $result = $this->testDriver->artisan('db:wipe --force');

    if ($result['exitCode'] !== 0) {
        $this->fail('db:wipe failed: '.implode("\n", $result['output']));
    }
});

test('install process works', function () {
    $page = $this->visit($this->browserUrl('/admin/install'))->page();

    // Step 1: Start
    $page->getByRole('button', ['name' => 'Install Craft CMS'])->click();

    // Step 2: Accept license
    $page->getByRole('button', ['name' => 'Got it'])->click();

    // Step 3: Account
    $page->getByRole('textbox', ['name' => 'Username'])->fill('admin');
    $page->getByRole('textbox', ['name' => 'Email'])->fill('test@craftcms.com');
    $page->getByRole('textbox', ['name' => 'Password'])->fill('NewPassword1!');
    $page->getByRole('button', ['name' => 'Next'])->click();

    // Step 4: Database
    $page->getByRole('combobox', ['name' => 'Driver'])->selectOption(env('DB_CONNECTION', 'mysql'));
    $page->getByRole('textbox', ['name' => 'Host'])->fill(env('DB_HOST', 'db'));
    $page->getByRole('textbox', ['name' => 'Port'])->fill((string) env('DB_PORT', '3306'));
    $page->getByRole('textbox', ['name' => 'Username'])->fill(env('DB_USERNAME', 'db'));
    $page->getByRole('textbox', ['name' => 'Password'])->fill((string) env('DB_PASSWORD', 'db'));
    $page->getByRole('textbox', ['name' => 'Database Name'])->fill(env('DB_DATABASE', 'db'));
    $page->getByRole('button', ['name' => 'Next'])->click();

    // Step 5: Site
    $page->getByRole('textbox', ['name' => 'System Name'])->waitFor();
    $page->getByRole('textbox', ['name' => 'System Name'])->fill('Craft Test Site');
    $page->getByRole('textbox', ['name' => 'Base URL'])->fill('$APP_URL');
    $page->getByRole('combobox', ['name' => 'Language'])->selectOption('en');
    $page->getByRole('button', ['name' => 'Finish up'])->click();

    // Wait for install to complete and redirect
    $page->getByText('Craft is installed!')->waitFor(['timeout' => 30000]);

    // InstallingScreen redirects after 1s delay; allow extra time for navigation
    \Illuminate\Support\Sleep::sleep(5);

    expect($page->url())->not->toContain('/install');
});
