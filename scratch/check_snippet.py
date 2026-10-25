import urllib.request

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
            idx = content.find('NACOC FORENSIC')
            if idx != -1:
                snippet = content[idx:idx+200].replace('\n', ' ')
                print(f"{u} => {snippet}")
            else:
                print(f"{u} => NACOC FORENSIC NOT FOUND (check auth redirect)")
    except Exception as e:
        print(f"{u} => {e}")
