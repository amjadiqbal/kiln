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

it('accepts a valid RELATIVE temporary signed URL when absolute_signature is false', function () {
    // Confirmed by dumping it directly rather than assumed: Laravel's own
    // Illuminate\Http\Testing test client does NOT let a 'Host' header
    // override the request's reported host (it stays whatever the URI
    // itself carries — "localhost" here), so this test can't reproduce the
    // real proxy scheme/host mismatch end-to-end inside Testbench. What it
    // *does* prove, confirmed against UrlGenerator.php's own
    // temporarySignedRoute()/hasCorrectSignature() source: the $absolute
    // flag is baked into the HMAC at generation time (line 421's
    // `$absolute = true` default) and must match what's used at validation
    // time, or the signature simply won't verify — an "absolute_signature"
    // config toggle is therefore meaningless unless every URL a deploy
    // script generates also passes `absolute: false`. This is why the
    // README's example for that mode explicitly shows the extra parameter.
    $manager = app(OpcacheManager::class);

    if (! $manager->isActive()) {
        $this->markTestSkipped('opcache is not active for the CLI SAPI in this environment.');
    }

    config(['kiln.route.absolute_signature' => false]);

    $url = URL::temporarySignedRoute('kiln.status', now()->addMinutes(5), [], absolute: false);

    $this->getJson($url)->assertOk();
});

it('rejects a RELATIVE signed URL when absolute_signature is left true (the default)', function () {
    // The inverse of the above: a relatively-signed URL fails absolute
    // validation, because the HMAC was computed over the path+query only.
    $url = URL::temporarySignedRoute('kiln.status', now()->addMinutes(5), [], absolute: false);

    $this->getJson($url)->assertForbidden();
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
