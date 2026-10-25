const { spawn } = require('child_process');

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chrome = spawn(chromePath, [
  '--headless=new',
  '--remote-debugging-port=9222',
  '--disable-gpu',
  '--no-sandbox',
  'http://127.0.0.1:8000/Forensic_Head'
]);

async function run() {
  await new Promise(r => setTimeout(r, 2500));
  try {
    const res = await fetch('http://localhost:9222/json');
    const targets = await res.json();
    console.log('Targets:', targets.map(t => ({ url: t.url, title: t.title })));
    const page = targets.find(t => t.url.includes('Forensic_Head') || t.url.includes('login'));
    if (!page) {
      console.log('No matching page target found');
      chrome.kill();
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
                hasOg: typeof window.og !== 'undefined',
                hasGlobusVar: typeof isGlobusModeActive !== 'undefined',
                openGlobusContainerExists: !!document.getElementById('openGlobusContainer'),
                tacOpenGlobusContainerExists: !!document.getElementById('tacOpenGlobusContainer'),
                ghanaMapSvgExists: !!document.getElementById('ghanaMapSvg'),
                tacMapStageExists: !!document.getElementById('tacMapStage'),
                tacMapStageHidden: document.getElementById('tacMapStage')?.classList.contains('hidden'),
                regionsCount: document.querySelectorAll('.region-polygon').length
              })
            `,
            returnByValue: true
          }
        }));
      }, 1500);
    };

    ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      if (data.method === 'Runtime.consoleAPICalled') {
        console.log('CONSOLE:', data.params.type, data.params.args.map(a => a.value || a.description));
      }
      if (data.method === 'Runtime.exceptionThrown') {
        console.error('EXCEPTION:', data.params.exceptionDetails?.text, data.params.exceptionDetails?.exception?.description);
      }
      if (data.id === 3) {
        console.log('\n=== VERIFICATION RESULTS ===');
        console.log(JSON.stringify(data.result.result.value, null, 2));
        ws.close();
        chrome.kill();
        process.exit(0);
      }
    };
  } catch (err) {
    console.error('Test error:', err);
    chrome.kill();
    process.exit(1);
  }
}

run();
