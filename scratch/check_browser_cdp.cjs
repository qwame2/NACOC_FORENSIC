const { spawn } = require('child_process');

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chrome = spawn(chromePath, [
  '--headless=new',
  '--remote-debugging-port=9222',
  '--disable-gpu',
  '--no-sandbox',
  'http://localhost/ForensicNacoc/public/test_pin_render.html'
]);

async function run() {
  await new Promise(r => setTimeout(r, 2000));
  try {
    const res = await fetch('http://localhost:9222/json');
    const targets = await res.json();
    const page = targets.find(t => t.url.includes('test_pin_render.html'));
    if (!page) {
      console.log('Page not found');
      return;
    }
    console.log('Found page target:', page.webSocketDebuggerUrl);

    const ws = new WebSocket(page.webSocketDebuggerUrl);
    ws.onopen = () => {
      ws.send(JSON.stringify({ id: 1, method: 'Runtime.enable' }));
      ws.send(JSON.stringify({ id: 2, method: 'Console.enable' }));
      ws.send(JSON.stringify({ id: 3, method: 'Log.enable' }));
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 4,
          method: 'Runtime.evaluate',
          params: {
            expression: `
              ({
                log: document.getElementById('log')?.innerText,
                canvasCount: document.querySelectorAll('canvas').length,
                hasGlobe: !!window.og
              })
            `,
            returnByValue: true
          }
        }));
      }, 2000);
    };

    ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      if (data.method === 'Runtime.consoleAPICalled') {
        console.log('BROWSER CONSOLE:', data.params.type, data.params.args.map(a => a.value || a.description));
      }
      if (data.method === 'Runtime.exceptionThrown') {
        console.error('BROWSER EXCEPTION:', data.params.exceptionDetails);
      }
      if (data.id === 4) {
        console.log('EVAL RESULT:', data.result.result.value);
        ws.close();
        chrome.kill();
      }
    };
  } catch (e) {
    console.error('Error:', e);
    chrome.kill();
  }
}

run();
