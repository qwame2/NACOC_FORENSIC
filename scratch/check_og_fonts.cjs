const fs = require('fs');

// Check what font files exist in public/vendor/openglobus/
const dir = 'public/vendor/openglobus';
const files = fs.readdirSync(dir);
console.log('Files in public/vendor/openglobus:', files);

// Check if fonts directory exists
const fontsDir = 'public/vendor/openglobus/fonts';
if (fs.existsSync(fontsDir)) {
  console.log('Fonts in openglobus/fonts:', fs.readdirSync(fontsDir));
} else {
  console.log('NO fonts directory in public/vendor/openglobus!');
}
