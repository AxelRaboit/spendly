import { describe, it, expect } from 'vitest';
import { PASSWORD_RULES, passwordValidator } from '@/utils/passwordRules';

describe('PASSWORD_RULES', () => {
    it('requires at least 8 characters', () => {
        const rule = PASSWORD_RULES.find((r) => r.key === 'length');
        expect(rule.test('1234567')).toBe(false);
        expect(rule.test('12345678')).toBe(true);
    });

    it('requires an uppercase letter', () => {
        const rule = PASSWORD_RULES.find((r) => r.key === 'uppercase');
        expect(rule.test('password')).toBe(false);
        expect(rule.test('Password')).toBe(true);
    });

    it('requires a number', () => {
        const rule = PASSWORD_RULES.find((r) => r.key === 'number');
        expect(rule.test('Password!')).toBe(false);
        expect(rule.test('Password1')).toBe(true);
    });

    it('requires a special character', () => {
        const rule = PASSWORD_RULES.find((r) => r.key === 'special');
        expect(rule.test('Password1')).toBe(false);
        expect(rule.test('Password1!')).toBe(true);
    });
});

describe('passwordValidator', () => {
    const t = (key) => key;

    it('returns null for a valid password', () => {
        const validate = passwordValidator(t);
        expect(validate('Password1!')).toBeNull();
    });

    it('returns an error key for a too-short password', () => {
        const validate = passwordValidator(t);
        expect(validate('Ab1!')).toBe('password.errors.too_short');
    });

    it('returns an error key when no uppercase', () => {
        const validate = passwordValidator(t);
        expect(validate('password1!')).toBe('password.errors.no_uppercase');
    });

    it('returns an error key when no number', () => {
        const validate = passwordValidator(t);
        expect(validate('Password!')).toBe('password.errors.no_number');
    });

    it('returns an error key when no special character', () => {
        const validate = passwordValidator(t);
        expect(validate('Password1')).toBe('password.errors.no_special');
    });

    it('returns an error for empty string', () => {
        const validate = passwordValidator(t);
        expect(validate('')).toBe('password.errors.too_short');
    });
});
