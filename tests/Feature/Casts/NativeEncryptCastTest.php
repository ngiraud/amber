<?php

declare(strict_types=1);

use App\Casts\NativeEncryptCast;
use Native\Desktop\Facades\System;

pest()->group('casts');

describe('NativeEncryptCast', function () {
    beforeEach(function () {
        $this->cast = new NativeEncryptCast;
    });

    describe('get', function () {
        it('returns null when payload is null', function () {
            expect($this->cast->get(null))->toBeNull();
        });

        it('returns payload as-is when System::canEncrypt throws', function () {
            System::shouldReceive('canEncrypt')->andThrow(new RuntimeException('NativePHP not available'));

            expect($this->cast->get('plain-value'))->toBe('plain-value');
        });

        it('returns payload as-is when canEncrypt returns false', function () {
            System::shouldReceive('canEncrypt')->andReturn(false);

            expect($this->cast->get('plain-value'))->toBe('plain-value');
        });

        it('returns decrypted value when canEncrypt is true', function () {
            System::shouldReceive('canEncrypt')->andReturn(true);
            System::shouldReceive('decrypt')->with('encrypted-value')->andReturn('decrypted-value');

            expect($this->cast->get('encrypted-value'))->toBe('decrypted-value');
        });

        it('returns null when decrypt throws', function () {
            System::shouldReceive('canEncrypt')->andReturn(true);
            System::shouldReceive('decrypt')->andThrow(new RuntimeException('Decryption failed'));

            expect($this->cast->get('bad-encrypted-value'))->toBeNull();
        });
    });

    describe('set', function () {
        it('returns null when payload is null', function () {
            expect($this->cast->set(null))->toBeNull();
        });

        it('returns encrypted value when canEncrypt is true', function () {
            System::shouldReceive('canEncrypt')->andReturn(true);
            System::shouldReceive('encrypt')->with('plain-value')->andReturn('encrypted-value');

            expect($this->cast->set('plain-value'))->toBe('encrypted-value');
        });

        it('returns payload as-is when canEncrypt returns false', function () {
            System::shouldReceive('canEncrypt')->andReturn(false);

            expect($this->cast->set('plain-value'))->toBe('plain-value');
        });

        it('returns payload as-is when canEncrypt throws', function () {
            System::shouldReceive('canEncrypt')->andThrow(new RuntimeException('NativePHP not available'));

            expect($this->cast->set('plain-value'))->toBe('plain-value');
        });
    });
});
