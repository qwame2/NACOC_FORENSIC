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
      
      // Login
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
                  return 'LOGGED_IN';
                }
              })()
            `,
            returnByValue: true
          }
        }));
      }, 1000);

      // Navigate to Forensic_Head
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 4,
          method: 'Runtime.evaluate',
          params: {
            expression: `window.location.href = 'http://127.0.0.1:8000/Forensic_Head';`
          }
        }));
      }, 3000);

      // Trigger Tactical Mode (openTacticalMode) and inspect
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 5,
          method: 'Runtime.evaluate',
          params: {
            expression: `
              (() => {
                openTacticalMode();
                const theatre = document.getElementById('tacticalOpsTheatre');
                const tacStage = document.getElementById('tacMapStage');
                const isTheatreVisible = !theatre.classList.contains('hidden');
                const isTacStageVisible = !tacStage.classList.contains('hidden');
                
                // Select Ashanti region
                selectRegionByName('Ashanti', true);

                return {
                  theatreVisible: isTheatreVisible,
                  tacStageVisible: isTacStageVisible,
                  selectedRegion: selectedRegionName,
                  tacDistrictsCount: document.querySelectorAll('#tacDistrictDeckGrid > div').length
                };
              })()
            `,
            returnByValue: true
          }
        }));
      }, 5500);
    };

    ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      if (data.id === 5) {
        console.log('\n=== TACTICAL THEATRE INTERACTION TEST ===');
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
