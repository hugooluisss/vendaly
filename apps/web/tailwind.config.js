const forms = require('@tailwindcss/forms');

module.exports = {
  content: ['./src/**/*.{html,ts}'],
  theme: {
    extend: {
      colors: {
        vendaly: {
          orange: '#ea580c',
          'orange-light': '#f97316',
          'orange-soft': '#fff7ed',
          slate: '#1e293b',
          muted: '#64748b',
          border: '#e7e5e4',
          'border-strong': '#cbd5e1',
          danger: '#b42318',
          shadow: '#1e293b33',
          'shadow-soft': '#1e293b0d',
          surface: '#ffffff',
          bg: '#fafaf9',
          background: '#fafaf9',
        },
        whatsapp: {
          green: '#25d366',
          'green-hover': '#1fbd5b',
          text: '#052e16',
        },
      },
    },
  },
  plugins: [forms({ strategy: 'class' })],
};
