const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
  '--headless=new',
  '--remote-debugging-port=9222',
  '--window-size=1600,1000',
  '--disable-gpu',
  '--no-sandbox',
  'http://127.0.0.1:8000/login'
]);

setTimeout(async () => {
  try {
    const res = await fetch('http://localhost:9222/json');
    const targets = await res.json();
    const page = targets.find(t => t.url.includes('login') || t.url.includes('Forensic_Head'));
    if (!page) { console.log('No page target'); process.exit(1); }
    const ws = new WebSocket(page.webSocketDebuggerUrl);

    ws.onopen = () => {
      ws.send(JSON.stringify({ id: 1, method: 'Runtime.enable' }));
      ws.send(JSON.stringify({ id: 2, method: 'Page.enable' }));
      
      // Login as anna
      ws.send(JSON.stringify({
        id: 3,
        method: 'Runtime.evaluate',
        params: {
          expression: `
            (async () => {
              const form = document.querySelector('#loginForm');
              if (!form) return 'NO_FORM';
              const formData = new FormData(form);
              formData.set('login_id', 'anna');
              formData.set('password', 'password123');
              const csrfToken = document.querySelector('input[name="_token"]')?.value || '';
              const resp = await fetch('http://127.0.0.1:8000/login', {
                method: 'POST',
                headers: {
                  'Accept': 'application/json',
                  'X-CSRF-TOKEN': csrfToken,
                  'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
              });
              return await resp.json();
            })()
          `,
          awaitPromise: true,
          returnByValue: true
        }
      }));

      // Navigate via CDP after login succeeds
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 4,
          method: 'Page.navigate',
          params: { url: 'http://127.0.0.1:8000/Forensic_Head' }
        }));
      }, 1200);

      // Capture screenshot after full load
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 5,
          method: 'Page.captureScreenshot',
          params: { format: 'png' }
        }));
      }, 4000);
    };

    ws.onmessage = (evt) => {
      const data = JSON.parse(evt.data);
      if (data.id === 5 && data.result && data.result.data) {
        const buf = Buffer.from(data.result.data, 'base64');
        const outPath = path.join(__dirname, 'clean_dashboard_overview.png');
        fs.writeFileSync(outPath, buf);
        console.log('Saved screenshot successfully to:', outPath);
        ws.close();
        chrome.kill();
        process.exit(0);
      }
    };
  } catch (err) {
    console.error('Error:', err);
    chrome.kill();
    process.exit(1);
  }
}, 2000);
