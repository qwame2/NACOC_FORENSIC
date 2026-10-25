import urllib.request, json

url = 'http://localhost/ForensicNacoc/public/api/dashboard/realtime'
res = urllib.request.urlopen(url, timeout=3).read().decode('utf-8')
data = json.loads(res)
for c in data['cases']:
    print(f"Case {c['case_number']} (Multiple: {c.get('is_multiple')}, Suspects: {c.get('suspects_count')}):")
    print("   Suspects:", [f"{s['name']} ({s['role']})" for s in c.get('suspects', [])])
    print(f"   Exhibits ({len(c.get('exhibits', []))}):")
    for ex in c.get('exhibits', []):
        print(f"      * {ex['title']} | Owner: {ex.get('associated_person')} | SuspectID: {ex.get('suspect_id')}")
