<?php

it('does not register the kiln routes when kiln.route.enabled is false (the default)', function () {
    expect(app('router')->has('kiln.status'))->toBeFalse();
    expect(app('router')->has('kiln.clear'))->toBeFalse();
    expect(app('router')->has('kiln.warm'))->toBeFalse();
});
