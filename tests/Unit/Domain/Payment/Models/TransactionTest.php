<?php

use Domain\Payment\Models\Transaction;

it('generates a deterministic transaction hash', function () {
    expect(Transaction::generateHash('123'))
        ->toBe(md5('sys#65687123$#$rstg@3'))
        ->and(Transaction::generateHash('123'))
        ->not->toBe(Transaction::generateHash('124'));
});
