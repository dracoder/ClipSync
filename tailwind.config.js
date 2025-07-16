/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    darkMode: "class",
    theme: {
        extend: {
            'landscape': {
              'raw': '(orientation: landscape)'
            },
        },
    },
    plugins: [require("daisyui"), require("@tailwindcss/typography")],
    daisyui: {
        themes: [
            {
                clipsync: {
                    primary: "#5A67D8",
                    secondary: "#E2E8F0",
                    accent: "#48BB78",
                    neutral: "#4A5568",
                    info: "#3182CE",
                    success: "#38A169",
                    warning: "#D69E2E",
                    error: "#E53E3E",
                },
            },
        ],
    },
};
