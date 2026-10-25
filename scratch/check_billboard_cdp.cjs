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
    if (!page) return;

    const ws = new WebSocket(page.webSocketDebuggerUrl);
    ws.onopen = () => {
      ws.send(JSON.stringify({ id: 1, method: 'Runtime.enable' }));
      setTimeout(() => {
        ws.send(JSON.stringify({
          id: 4,
          method: 'Runtime.evaluate',
          params: {
            expression: `
              (() => {
                const ents = window.testVectorLayer ? window.testVectorLayer.getEntities() : [];
                return {
                  count: ents.length,
                  firstBillboardImage: !!ents[0]?.billboard?._image,
                  firstImageComplete: ents[0]?.billboard?._image?.complete,
                  firstImageWidth: ents[0]?.billboard?._image?.width,
                  firstImageNaturalWidth: ents[0]?.billboard?._image?.naturalWidth,
                  firstSrc: ents[0]?.billboard?.getSrc()?.slice(0, 50)
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
      if (data.id === 4) {
        console.log('BILLBOARD EVAL RESULT:', data.result.result.value);
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
