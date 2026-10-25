const { spawn } = require('child_process');

const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
  '--headless=new',
  '--remote-debugging-port=9222',
  '--disable-gpu',
  '--no-sandbox',
  'http://localhost/ForensicNacoc/public/login'
]);

setTimeout(async () => {
  try {
    const res = await fetch('http://localhost:9222/json');
    const targets = await res.json();
    const page = targets.find(t => t.url.includes('login') || t.url.includes('Evidence_Management'));
    if (!page) { console.log('No page target'); process.exit(1); }
    const ws = new WebSocket(page.webSocketDebuggerUrl);
    ws.onopen = () => {
      ws.send(JSON.stringify({ id: 1, method: 'Runtime.enable' }));
      ws.send(JSON.stringify({ id: 2, method: 'Console.enable' }));
      ws.send(JSON.stringify({
        id: 3,
        method: 'Runtime.evaluate',
        params: {
          expression: `
            (async () => {
              const form = document.querySelector('#loginForm');
              if (!form) return 'NO_FORM';
              const formData = new FormData(form);
              formData.set('login_id', 'diana');
              formData.set('password', 'password123');
              const csrfToken = document.querySelector('input[name="_token"]')?.value || '';
              const resp = await fetch('http://localhost/ForensicNacoc/public/login', {
                method: 'POST',
                headers: {
                  'Accept': 'application/json',
                  'X-CSRF-TOKEN': csrfToken,
                  'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
              });
              const resJson = await resp.json();
              console.log('LOGIN_FETCH_RESULT:', JSON.stringify(resJson));
              if (resJson.success) {
                window.location.href = 'http://localhost/ForensicNacoc/public/Evidence_Management';
              }
              return resJson;
            })()
          `,
          awaitPromise: true,
          returnByValue: true
        }
      }));

      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 5,
          method: 'Runtime.evaluate',
          params: {
            expression: `
              (() => {
                const canvas = document.querySelector('#globusCanvasWrapper canvas');
                const hasOg = !!window.og;
                const entities = (typeof globusMarkersLayer !== 'undefined' && globusMarkersLayer) ? globusMarkersLayer.getEntities() : [];
                const markerSummary = entities.map(e => ({
                  name: e.name,
                  hasBillboard: !!e.billboard,
                  billboardW: e.billboard ? e.billboard.getWidth() : null,
                  billboardH: e.billboard ? e.billboard.getHeight() : null,
                  lon: e.lonlat ? e.lonlat.lon : null,
                  lat: e.lonlat ? e.lonlat.lat : null
                }));

                // Open Tactical Theatre to test fullscreen mode as well
                if (typeof openTacticalMode === 'function') {
                  openTacticalMode();
                }

                return {
                  href: window.location.href,
                  title: document.title,
                  hasOg,
                  canvasExists: !!canvas,
                  canvasWidth: canvas ? canvas.width : 0,
                  canvasHeight: canvas ? canvas.height : 0,
                  liveCasesCount: (typeof liveCases !== 'undefined' && Array.isArray(liveCases)) ? liveCases.length : 0,
                  globusInstanceExists: typeof globusInstance !== 'undefined' && !!globusInstance,
                  markersCount: entities.length,
                  markerSummary: markerSummary.slice(0, 8),
                  hotspotEntity: markerSummary.find(m => m.name.includes('HOTSPOT')),
                  raidEntities: markerSummary.filter(m => m.name.includes('RAID')),
                  targetEntity: markerSummary.find(m => m.name.includes('TARGET'))
                };
              })()
            `,
            returnByValue: true
          }
        }));
      }, 7000);

      // Step 6: Check Tactical Theatre globe 2 seconds after openTacticalMode()
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 6,
          method: 'Runtime.evaluate',
          params: {
            expression: `
              (() => {
                const tacCanvas = document.querySelector('#tacGlobusCanvasWrapper canvas');
                const tacEntities = (typeof tacGlobusMarkersLayer !== 'undefined' && tacGlobusMarkersLayer) ? tacGlobusMarkersLayer.getEntities() : [];
                return {
                  tacCanvasExists: !!tacCanvas,
                  tacCanvasWidth: tacCanvas ? tacCanvas.width : 0,
                  tacCanvasHeight: tacCanvas ? tacCanvas.height : 0,
                  tacGlobusInstanceExists: typeof tacGlobusInstance !== 'undefined' && !!tacGlobusInstance,
                  tacMarkersCount: tacEntities.length,
                  tacHotspot: tacEntities.find(e => e.name.includes('HOTSPOT'))?.name,
                  tacTarget: tacEntities.find(e => e.name.includes('TARGET'))?.name,
                  tacRaids: tacEntities.filter(e => e.name.includes('RAID')).map(e => e.name)
                };
              })()
            `,
            returnByValue: true
          }
        }));
      }, 9500);
    };

    ws.onmessage = (msg) => {
      const data = JSON.parse(msg.data);
      if (data.id === 3) {
        console.log('ID 3 LOGIN RESULT:', JSON.stringify(data, null, 2));
      }
      if (data.id === 5) {
        console.log('MAIN GLOBE INSPECTION RESULT:', JSON.stringify(data.result, null, 2));
      }
      if (data.id === 6) {
        console.log('TACTICAL THEATRE GLOBE INSPECTION RESULT:', JSON.stringify(data.result, null, 2));
        process.exit(0);
      }
      if (data.method === 'Runtime.consoleAPICalled') {
        console.log('CONSOLE:', data.params.type, data.params.args.map(a => a.value || a.description).join(' '));
      }
      if (data.method === 'Runtime.exceptionThrown') {
        console.log('EXCEPTION:', data.params.exceptionDetails);
      }
    };
  } catch (err) {
    console.error(err);
    process.exit(1);
  }
}, 1500);
