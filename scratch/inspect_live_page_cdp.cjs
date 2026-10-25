const { spawn } = require('child_process');

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chrome = spawn(chromePath, [
  '--headless=new',
  '--remote-debugging-port=9222',
  '--disable-gpu',
  '--no-sandbox',
  'http://localhost/ForensicNacoc/public/Evidence_Management'
]);

async function run() {
  await new Promise(r => setTimeout(r, 2500));
  try {
    const res = await fetch('http://localhost:9222/json');
    const targets = await res.json();
    console.log('Targets:', targets.map(t => ({ url: t.url, title: t.title })));
    const page = targets.find(t => t.url.includes('Evidence_Management') || t.url.includes('login'));
    if (!page) {
      console.log('No matching target');
      return;
    }

    const ws = new WebSocket(page.webSocketDebuggerUrl);
    ws.onopen = () => {
      ws.send(JSON.stringify({ id: 1, method: 'Runtime.enable' }));
      ws.send(JSON.stringify({ id: 2, method: 'Console.enable' }));
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 3,
          method: 'Runtime.evaluate',
          params: {
            expression: `
              ({
                url: window.location.href,
                title: document.title,
                hasOg: !!window.og,
                isGlobusActive: typeof isGlobusModeActive !== 'undefined' ? isGlobusModeActive : null,
                globusContainerHidden: document.getElementById('openGlobusContainer')?.classList.contains('hidden'),
                canvasCount: document.querySelectorAll('canvas').length,
                markersCount: window.globusMarkersLayer ? window.globusMarkersLayer.getEntities().length : null
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
        console.log('PAGE CONSOLE:', data.params.type, data.params.args.map(a => a.value || a.description));
      }
      if (data.method === 'Runtime.exceptionThrown') {
        console.error('PAGE EXCEPTION:', data.params.exceptionDetails);
      }
      if (data.id === 3) {
        console.log('PAGE STATE:', data.result.result.value);
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
