import { describe, it, expect } from 'vitest';
import { evalMath } from '@/utils/evalMath';

describe('evalMath', () => {
    it('evaluates a simple addition', () => {
        expect(evalMath('1 + 2')).toBe(3);
    });

    it('evaluates a subtraction', () => {
        expect(evalMath('10 - 4.5')).toBe(5.5);
    });

    it('evaluates a multiplication', () => {
        expect(evalMath('3 * 4')).toBe(12);
    });

    it('evaluates a division', () => {
        expect(evalMath('10 / 4')).toBe(2.5);
    });

    it('replaces commas with dots', () => {
        expect(evalMath('1,5 + 2,5')).toBe(4);
    });

    it('rounds to 2 decimal places', () => {
        expect(evalMath('1 / 3')).toBe(0.33);
    });

    it('evaluates parentheses', () => {
        expect(evalMath('(2 + 3) * 4')).toBe(20);
    });

    it('returns null for non-string input', () => {
        expect(evalMath(42)).toBeNull();
        expect(evalMath(null)).toBeNull();
    });

    it('returns null for empty string', () => {
        expect(evalMath('')).toBeNull();
    });

    it('returns null for invalid expression', () => {
        expect(evalMath('alert(1)')).toBeNull();
        expect(evalMath('2 + a')).toBeNull();
    });

    it('returns null for division by zero', () => {
        expect(evalMath('1 / 0')).toBeNull();
    });
});
