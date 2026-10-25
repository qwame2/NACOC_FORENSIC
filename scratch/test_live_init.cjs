const { spawn } = require('child_process');

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chrome = spawn(chromePath, [
  '--headless=new',
  '--remote-debugging-port=9222',
  '--disable-gpu',
  '--no-sandbox',
  'http://localhost/ForensicNacoc/public/login'
]);

async function run() {
  await new Promise(r => setTimeout(r, 2000));
  try {
    const res = await fetch('http://localhost:9222/json');
    const targets = await res.json();
    const page = targets.find(t => t.url.includes('login') || t.url.includes('Evidence_Management'));

    const ws = new WebSocket(page.webSocketDebuggerUrl);
    ws.onopen = () => {
      ws.send(JSON.stringify({ id: 1, method: 'Runtime.enable' }));
      ws.send(JSON.stringify({ id: 2, method: 'Console.enable' }));
      
      // Submit login
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 3,
          method: 'Runtime.evaluate',
          params: {
            expression: `
              document.querySelector('input[name="username"]').value = 'anna';
              document.querySelector('input[name="password"]').value = 'password123';
              document.querySelector('button[type="submit"]').click();
            `
          }
        }));
      }, 500);

      // Navigate to Evidence_Management
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 4,
          method: 'Runtime.evaluate',
          params: { expression: "window.location.href = 'http://localhost/ForensicNacoc/public/Evidence_Management';" }
        }));
      }, 2000);

      // Test initOpenGlobusEngine explicitly
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 5,
          method: 'Runtime.evaluate',
          params: {
            expression: `
              (() => {
                let initErr = null;
                try {
                  initOpenGlobusEngine();
                } catch(e) {
                  initErr = e.message + ' stack: ' + e.stack;
                }
                return {
                  hasOg: !!window.og,
                  container: !!document.getElementById('globusCanvasWrapper'),
                  globusInstance: !!globusInstance,
                  initErr: initErr,
                  entitiesCount: globusMarkersLayer ? globusMarkersLayer.getEntities().length : null
                };
              })()
            `,
            returnByValue: true
          }
        }));
      }, 6000);
    };

    ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      if (data.method === 'Runtime.consoleAPICalled') {
        console.log('CONSOLE:', data.params.type, data.params.args.map(a => a.value || a.description));
      }
      if (data.method === 'Runtime.exceptionThrown') {
        console.error('EXCEPTION:', data.params.exceptionDetails);
      }
      if (data.id === 5) {
        console.log('INIT RESULT:', data.result.result.value);
        ws.close();
        chrome.kill();
      }
    };
  } catch (e) {
    console.error(e);
    chrome.kill();
  }
}

run();
