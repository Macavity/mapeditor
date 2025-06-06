/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: 'class',
    content: ['./resources/**/*.blade.php', './resources/**/*.js', './resources/**/*.vue'],
    theme: {
        extend: {
            colors: {
                primary: 'var(--color-primary)',
                'primary-foreground': 'var(--color-primary-foreground)',
                secondary: 'var(--color-secondary)',
                'secondary-foreground': 'var(--color-secondary-foreground)',
            },
            backgroundColor: {
                'primary/10': 'color-mix(in srgb, var(--color-primary) 10%, transparent)',
                'secondary/10': 'color-mix(in srgb, var(--color-secondary) 10%, transparent)',
            },
        },
    },
    plugins: [],
};
