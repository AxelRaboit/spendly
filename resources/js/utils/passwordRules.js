export const PASSWORD_RULES = [
    { key: 'length', test: (p) => p.length >= 8, errorKey: 'password.errors.too_short' },
    { key: 'uppercase', test: (p) => /[A-Z]/.test(p), errorKey: 'password.errors.no_uppercase' },
    { key: 'number', test: (p) => /[0-9]/.test(p), errorKey: 'password.errors.no_number' },
    { key: 'special', test: (p) => /[^A-Za-z0-9]/.test(p), errorKey: 'password.errors.no_special' },
];

export function passwordValidator(t) {
    return (value) => {
        for (const rule of PASSWORD_RULES) {
            if (!value || !rule.test(value)) return t(rule.errorKey);
        }
        return null;
    };
}
