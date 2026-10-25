const puppeteer = require('puppeteer');
const fs = require('fs');

(async () => {
  console.log("Starting Chrome Headless to test 3D Globe Map Point Icons & Symbols...");
  const browser = await puppeteer.launch({
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--use-gl=angle', '--use-angle=swiftshader']
  });
  const page = await browser.newPage();
  
  page.on('console', msg => console.log('PAGE LOG:', msg.text()));
  page.on('pageerror', err => console.error('PAGE ERROR:', err.message));

  await page.goto('http://127.0.0.1/public/test_pin_render.html', { waitUntil: 'networkidle0', timeout: 15000 });

  // Evaluate if billboards are rendered
  const status = await page.evaluate(() => {
    return {
      hasOg: !!window.og,
      billboardElements: document.querySelectorAll('canvas').length
    };
  });

  console.log("Test Pin Render Status:", status);
  await browser.close();
  console.log("Chrome Headless test completed.");
})();
