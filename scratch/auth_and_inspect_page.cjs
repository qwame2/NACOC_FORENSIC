const { spawn } = require('child_process');

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chrome = spawn(chromePath, [
  '--headless=new',
  '--remote-debugging-port=9222',
  '--disable-gpu',
  '--no-sandbox',
  'http://127.0.0.1:8000/login'
]);

async function run() {
  await new Promise(r => setTimeout(r, 2000));
  try {
    const res = await fetch('http://localhost:9222/json');
    const targets = await res.json();
    const page = targets.find(t => t.url.includes('login') || t.url.includes('Forensic_Head'));
    if (!page) {
      console.log('No page found');
      chrome.kill();
      return;
    }

    const ws = new WebSocket(page.webSocketDebuggerUrl);
    ws.onopen = () => {
      ws.send(JSON.stringify({ id: 1, method: 'Runtime.enable' }));
      ws.send(JSON.stringify({ id: 2, method: 'Console.enable' }));
      
      // Submit login form
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 3,
          method: 'Runtime.evaluate',
          params: {
            expression: `
              (() => {
                const u = document.querySelector('input[name="login_id"]');
                const p = document.querySelector('input[name="password"]');
                const form = document.getElementById('loginForm');
                if (u && p && form) {
                  u.value = 'anna';
                  p.value = 'password123';
                  form.submit();
                  return 'SUBMITTED_LOGIN_FORM';
                }
                return 'FORM_OR_INPUTS_NOT_FOUND';
              })()
            `,
            returnByValue: true
          }
        }));
      }, 1000);

      // Wait for session and navigate to Forensic_Head
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 4,
          method: 'Runtime.evaluate',
          params: {
            expression: `
              window.location.href = 'http://127.0.0.1:8000/Forensic_Head';
            `
          }
        }));
      }, 3000);

      // Inspect elements on Forensic_Head dashboard
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 5,
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
                btnToggleEngineModeExists: !!document.getElementById('btnToggleEngineMode'),
                ghanaMapSvgExists: !!document.getElementById('ghanaMapSvg'),
                tacMapStageExists: !!document.getElementById('tacMapStage'),
                tacMapStageHidden: document.getElementById('tacMapStage')?.classList.contains('hidden'),
                regionsCount: document.querySelectorAll('.region-polygon').length,
                redHotspotsCount: document.querySelectorAll('.region-hotspot').length,
                dossierCardVisible: !!document.getElementById('panelForensicDossier'),
                tacticalOpsTheatreExists: !!document.getElementById('tacticalOpsTheatre')
              })
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
        console.error('EXCEPTION:', data.params.exceptionDetails?.text, data.params.exceptionDetails?.exception?.description);
      }
      if (data.id === 5) {
        console.log('\n=== LIVE AUTHENTICATED DASHBOARD STATE ===');
        console.log(JSON.stringify(data.result.result.value, null, 2));
        ws.close();
        chrome.kill();
        process.exit(0);
      }
    };
  } catch (e) {
    console.error('Error:', e);
    chrome.kill();
    process.exit(1);
  }
}

run();
