<?php

use Application\Api\User\Rules\NicknameCheck;
use Illuminate\Support\Facades\Validator;

it('accepts nicknames without reserved words', function (string $nickname) {
    $rule = new NicknameCheck;
    $failed = false;

    $rule->validate('nickname', $nickname, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
})->with([
    'cooluser',
    'shop_owner',
    'محمد',
    'AdaLovelace',
]);

it('rejects nicknames containing admin in english or persian', function (string $nickname) {
    $rule = new NicknameCheck;
    $message = null;

    $rule->validate('nickname', $nickname, function ($error) use (&$message) {
        $message = $error;
    });

    expect($message)->toBe(trans('site.Invalid Nickname'));
})->with([
    'admin',
    'siteadmin',
    'my_admin_user',
    'ادمین',
    'کاربر_ادمین',
]);

it('works inside laravel validator', function () {
    $validator = Validator::make(
        ['nickname' => 'superadmin'],
        ['nickname' => [new NicknameCheck]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('nickname'))->toBe(trans('site.Invalid Nickname'));
});
