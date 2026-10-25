const http = require('http');

async function test() {
  // Login first via fetch
  const loginRes = await fetch('http://localhost/ForensicNacoc/public/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: 'login_id=diana&password=password123'
  });
  
  const cookies = loginRes.headers.getSetCookie ? loginRes.headers.getSetCookie() : [loginRes.headers.get('set-cookie')];
  const cookieStr = cookies.map(c => c.split(';')[0]).join('; ');

  const pageRes = await fetch('http://localhost/ForensicNacoc/public/Evidence_Management', {
    headers: {
      'Cookie': cookieStr
    }
  });

  const html = await pageRes.text();
  const fs = require('fs');
  fs.writeFileSync('scratch/rendered_page.html', html);
  console.log('Saved rendered_page.html, total lines:', html.split('\n').length);
  
  // Find line 1895 (0-indexed 1894 or around it)
  const lines = html.split('\n');
  for (let i = 1880; i < Math.min(1915, lines.length); i++) {
    console.log(`L${i+1}: ${lines[i].slice(0, 100)}`);
  }
}

test().catch(console.error);
