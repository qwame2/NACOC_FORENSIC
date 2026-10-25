import urllib.request
import re

urls = [
    'http://127.0.0.1:8000/Lab_Analysis',
    'http://127.0.0.1:8000/Lab_Analysis?from=Crime+Scene+Support',
    'http://127.0.0.1:8000/lab-analysis',
    'http://127.0.0.1:8000/Crime_Scene_Support/head',
    'http://127.0.0.1:8000/Evidence_Management'
]

for u in urls:
    try:
        req = urllib.request.Request(u, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, timeout=4) as resp:
            content = resp.read().decode('utf-8', errors='ignore')
            m = re.search(r'title="([^"]*UNIT)"', content)
            title = m.group(1) if m else 'NOT FOUND'
            print(f"{u.replace('http://127.0.0.1:8000', '')} => {title}")
    except Exception as e:
        print(f"{u.replace('http://127.0.0.1:8000', '')} => Error: {e}")
