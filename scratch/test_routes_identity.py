import urllib.request, re

for path in ['/dashboard', '/finnova-dashboard']:
    url = f'http://localhost/ForensicNacoc/public{path}'
    res = urllib.request.urlopen(url, timeout=3).read().decode('utf-8')
    title = re.findall(r'<title>(.*?)</title>', res)[0]
    badge = re.findall(r'NACOC FORENSIC.*?<span[^>]*class="[^"]*font-mono-code[^"]*"[^>]*>\s*(.*?)\s*</span>', res, re.DOTALL)[0]
    card4 = re.findall(r'<!-- CARD 4:\s*(.*?)\s*-->', res)[0]
    print(f'{path} -> Title: {title} | Badge: {badge} | Card4: {card4}')
