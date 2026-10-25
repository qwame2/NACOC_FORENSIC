const { spawn } = require('child_process');

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chrome = spawn(chromePath, [
  '--headless=new',
  '--remote-debugging-port=9222',
  '--disable-gpu',
  '--no-sandbox',
  'http://localhost/ForensicNacoc/public/test_pin_no_label.html'
]);

async function run() {
  await new Promise(r => setTimeout(r, 2000));
  try {
    const res = await fetch('http://localhost:9222/json');
    const targets = await res.json();
    const page = targets.find(t => t.url.includes('test_pin_no_label.html'));
    if (!page) return;

    let exceptions = [];
    const ws = new WebSocket(page.webSocketDebuggerUrl);
    ws.onopen = () => {
      ws.send(JSON.stringify({ id: 1, method: 'Runtime.enable' }));
      ws.send(JSON.stringify({ id: 2, method: 'Console.enable' }));
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 4,
          method: 'Runtime.evaluate',
          params: {
            expression: `
              (() => {
                const ent = window.vectorLayer ? window.vectorLayer.getEntities()[0] : null;
                return {
                  hasEnt: !!ent,
                  hasImage: !!ent?.billboard?._image,
                  imageComplete: ent?.billboard?._image?.complete,
                  imageWidth: ent?.billboard?._image?.width,
                  imageHeight: ent?.billboard?._image?.height,
                  testPassed: window.testPassed,
                  exceptionsCount: ${exceptions.length}
                };
              })()
            `,
            returnByValue: true
          }
        }));
      }, 3000);
    };

    ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      if (data.method === 'Runtime.consoleAPICalled') {
        console.log('CONSOLE:', data.params.type, data.params.args.map(a => a.value || a.description));
      }
      if (data.method === 'Runtime.exceptionThrown') {
        console.error('EXCEPTION:', data.params.exceptionDetails);
        exceptions.push(data.params.exceptionDetails);
      }
      if (data.id === 4) {
        console.log('RESULT:', data.result.result.value);
        console.log('TOTAL EXCEPTIONS:', exceptions.length);
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
