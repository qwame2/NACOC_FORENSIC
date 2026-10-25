const { spawn } = require('child_process');
const http = require('http');

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chrome = spawn(chromePath, [
  '--headless=new',
  '--remote-debugging-port=9222',
  '--disable-gpu',
  '--no-sandbox',
  'http://localhost/ForensicNacoc/public/test_pin_render.html'
]);

async function check() {
  await new Promise(r => setTimeout(r, 2000));
  try {
    const res = await fetch('http://localhost:9222/json');
    const targets = await res.json();
    console.log('Targets:', targets.map(t => ({ url: t.url, title: t.title })));
  } catch (e) {
    console.error('Fetch error:', e.message);
  } finally {
    chrome.kill();
  }
}

check();
