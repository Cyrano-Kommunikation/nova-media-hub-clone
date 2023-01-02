const path = require('path');

module.exports = {
  content: [path.resolve(__dirname, 'resources/**/*.{vue,js,ts,jsx,tsx,scss}')],
  prefix: 'o1-',
  darkMode: 'class',
  safelist: [
    'text-red-500',
    'text-slate-600',
    'o1-h-24',
    'o1-h-32',
    'o1-h-36',
    'o1-h-40',
    'o1-h-48',
    'o1-w-24',
    'o1-w-32',
    'o1-w-36',
    'o1-w-40',
    'o1-w-48',
  ],
  theme: {
    extend: {
      'hav-red': {
        primary: '#e10019',
        light: 'rgba(225, 0, 25, 0.1)',
      },
      'hav-blue': {
        primary: '#2d4673',
        'primary-medium': 'rgba(45, 70, 115, 0.3)',
        'primary-light': 'rgba(45, 70, 115, 0.05)',
        secondary: '#0096c8',
      },
      'hav-gray': {
        light: '#c8c8c8',
        medium: '#787878',
        dark: '#505050'
      },
    }
  }
};
