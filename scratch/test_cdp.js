const http = require('http');
const { spawn } = require('child_process');

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chrome = spawn(chromePath, [
  '--headless',
  '--remote-debugging-port=9222',
  '--disable-gpu',
  'http://localhost/ForensicNacoc/public/test_dual_globus.html'
]);

setTimeout(async () => {
  try {
    const res = await fetch('http://localhost:9222/json');
    const targets = await res.json();
    console.log('Targets:', targets.map(t => ({ url: t.url, title: t.title })));
    
    // Connect to WebSocket
    const pageTarget = targets.find(t => t.type === 'page');
    if (pageTarget) {
      const WebSocket = require('ws'); // might not be installed, check fetch
    }
  } catch (e) {
    console.log('Error:', e.message);
  } finally {
    chrome.kill();
  }
}, 2000);
