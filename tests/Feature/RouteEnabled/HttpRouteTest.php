<?php

use AmjadIqbal\Kiln\OpcacheManager;
use Illuminate\Support\Facades\URL;

it('rejects a request with no signature at all', function () {
    $this->getJson('/kiln/opcache/status')->assertForbidden();
});

it('rejects a PERMANENT signed URL — temporary signatures are required', function () {
    $url = URL::signedRoute('kiln.status');

    $this->getJson($url)->assertForbidden();
});

it('rejects an expired temporary signed URL', function () {
    $url = URL::temporarySignedRoute('kiln.status', now()->subMinute());

    $this->getJson($url)->assertForbidden();
});

// The two below need a real, active OPcache to reach a 200 — the controller
// correctly 500s otherwise, which is right behaviour, not a test bug. CI
// always runs with opcache.enable_cli=1 (see ci.yml); locally, run
// `php -d opcache.enable_cli=1 vendor/bin/pest` to exercise this path.

it('accepts a valid TEMPORARY signed URL', function () {
    $manager = app(OpcacheManager::class);

    if (! $manager->isActive()) {
        $this->markTestSkipped('opcache is not active for the CLI SAPI in this environment.');
    }

    $url = URL::temporarySignedRoute('kiln.status', now()->addMinutes(5));

    $this->getJson($url)->assertOk()->assertJson(['ok' => true]);
});

it('registers clear and warm as POST-only, not GET', function () {
    $manager = app(OpcacheManager::class);

    $url = URL::temporarySignedRoute('kiln.clear', now()->addMinutes(5));

    $this->getJson($url)->assertStatus(405);

    if (! $manager->isActive()) {
        $this->markTestSkipped('opcache is not active for the CLI SAPI in this environment.');
    }

    $this->postJson($url)->assertOk();
});
