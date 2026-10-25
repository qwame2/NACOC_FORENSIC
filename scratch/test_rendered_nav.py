import urllib.request, re

url = 'http://localhost/ForensicNacoc/public/dashboard'
res = urllib.request.urlopen(url, timeout=3).read().decode('utf-8')
nav = re.search(r'<nav[^>]*>(.*?)</nav>', res, re.DOTALL)
if nav:
    links = re.findall(r'<a[^>]*href="([^"]*)"[^>]*>.*?<span>(.*?)</span>.*?</a>', nav.group(1), re.DOTALL)
    print("Rendered Dashboard Nav Links:")
    for h, t in links:
        print(f"  - {t.strip()} -> {h.strip()}")
