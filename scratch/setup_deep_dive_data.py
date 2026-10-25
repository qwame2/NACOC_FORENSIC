import json
import os
import re

with open('scratch/ghana_regions_svg_optimized.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

regions = data['regions']

# Bounding boxes and viewboxes for all 16 regions
region_bounds = {}
for r in regions:
    coords = re.findall(r'([0-9]+\.[0-9]+),([0-9]+\.[0-9]+)', r['path'])
    xs = [float(x) for x, y in coords]
    ys = [float(y) for x, y in coords]
    min_x, max_x = min(xs), max(xs)
    min_y, max_y = min(ys), max(ys)
    w = max_x - min_x
    h = max_y - min_y
    pad_x = max(w * 0.16, 20.0)
    pad_y = max(h * 0.16, 20.0)
    vb = f"{min_x - pad_x:.1f} {min_y - pad_y:.1f} {w + 2*pad_x:.1f} {h + 2*pad_y:.1f}"
    region_bounds[r['name']] = {
        'minX': round(min_x, 1),
        'maxX': round(max_x, 1),
        'minY': round(min_y, 1),
        'maxY': round(max_y, 1),
        'viewBox': vb,
        'cx': r['cx'],
        'cy': r['cy']
    }

# Comprehensive towns for all 16 regions
towns_data = {
    'Greater Accra': [
        {'name': 'Accra Central', 'type': 'National Headquarters Hub', 'status': 'Command HQ', 'officers': 46, 'cases': 520, 'badge': 'HQ Central', 'x': 532.0, 'y': 786.0},
        {'name': 'Tema Port', 'type': 'Seaport & Maritime Cargo Intake', 'status': 'High Alert', 'officers': 32, 'cases': 285, 'badge': 'Port Security', 'x': 565.0, 'y': 782.0},
        {'name': 'Madina', 'type': 'Northern Sector Intercept Post', 'status': 'Active Patrol', 'officers': 18, 'cases': 142, 'badge': 'Sector Station', 'x': 536.0, 'y': 768.0},
        {'name': 'Adenta', 'type': 'Aviation Corridor Checkpoint', 'status': 'Surveillance', 'officers': 14, 'cases': 98, 'badge': 'Checkpoint', 'x': 548.0, 'y': 762.0},
        {'name': 'Ashaiman', 'type': 'Industrial Transit & Tactical Base', 'status': 'Interception', 'officers': 22, 'cases': 115, 'badge': 'Tactical Unit', 'x': 562.0, 'y': 774.0},
        {'name': 'Prampram', 'type': 'Littoral Coastal Radar Post', 'status': 'Monitoring', 'officers': 10, 'cases': 48, 'badge': 'Coastal Radar', 'x': 588.0, 'y': 772.0},
        {'name': 'Ada Foah', 'type': 'Volta Estuary Riverine Post', 'status': 'Active Patrol', 'officers': 12, 'cases': 40, 'badge': 'Estuary Patrol', 'x': 616.0, 'y': 764.0},
    ],
    'Ashanti': [
        {'name': 'Kumasi Central', 'type': 'Regional Command Headquarters', 'status': 'Command HQ', 'officers': 32, 'cases': 340, 'badge': 'Regional HQ', 'x': 354.5, 'y': 668.0},
        {'name': 'Obuasi', 'type': 'Goldfield Basin Operations Command', 'status': 'High Alert', 'officers': 18, 'cases': 120, 'badge': 'Mining Post', 'x': 310.0, 'y': 730.0},
        {'name': 'Konongo', 'type': 'Eastern Highway Intercept Checkpoint', 'status': 'Active Patrol', 'officers': 14, 'cases': 74, 'badge': 'Highway Post', 'x': 415.0, 'y': 665.0},
        {'name': 'Mampong', 'type': 'Northern Escarpment Command', 'status': 'Normal Patrol', 'officers': 12, 'cases': 62, 'badge': 'Escarpment Post', 'x': 370.0, 'y': 590.0},
        {'name': 'Bekwai', 'type': 'Southern Transit Interception Post', 'status': 'Surveillance', 'officers': 10, 'cases': 39, 'badge': 'Field Unit', 'x': 335.0, 'y': 705.0},
        {'name': 'Ejisu', 'type': 'Logistics & Intercept Station', 'status': 'Active Patrol', 'officers': 11, 'cases': 45, 'badge': 'Transit Post', 'x': 382.0, 'y': 658.0},
    ],
    'Western': [
        {'name': 'Sekondi-Takoradi', 'type': 'Twin-City Maritime Operations Hub', 'status': 'Command HQ', 'officers': 24, 'cases': 210, 'badge': 'Maritime Base', 'x': 275.0, 'y': 910.0},
        {'name': 'Tarkwa', 'type': 'Mineral Belt Extraction Intelligence', 'status': 'High Alert', 'officers': 18, 'cases': 130, 'badge': 'Mining Unit', 'x': 225.0, 'y': 845.0},
        {'name': 'Elubo', 'type': 'Ivory Coast Western Border Command', 'status': 'Border Control', 'officers': 20, 'cases': 145, 'badge': 'Border Command', 'x': 135.0, 'y': 868.0},
        {'name': 'Axim', 'type': 'Deep-Sea Offshore Surveillance Watch', 'status': 'Active Patrol', 'officers': 12, 'cases': 55, 'badge': 'Coast Guard', 'x': 200.0, 'y': 885.0},
        {'name': 'Bogoso', 'type': 'Inland Transit Tactical Checkpoint', 'status': 'Normal Patrol', 'officers': 10, 'cases': 42, 'badge': 'Field Station', 'x': 210.0, 'y': 800.0},
    ],
    'Central': [
        {'name': 'Cape Coast', 'type': 'Regional Headquarters & Operations', 'status': 'Command HQ', 'officers': 16, 'cases': 120, 'badge': 'Regional HQ', 'x': 355.0, 'y': 870.0},
        {'name': 'Winneba', 'type': 'Coastal Highway Intercept Post', 'status': 'Active Patrol', 'officers': 12, 'cases': 65, 'badge': 'Highway Base', 'x': 435.0, 'y': 848.0},
        {'name': 'Mankessim', 'type': 'Central Transit Market Station', 'status': 'Surveillance', 'officers': 10, 'cases': 48, 'badge': 'Transit Post', 'x': 385.0, 'y': 862.0},
        {'name': 'Elmina', 'type': 'Fisheries Maritime Station', 'status': 'Normal Patrol', 'officers': 9, 'cases': 32, 'badge': 'Fisheries Post', 'x': 338.0, 'y': 876.0},
        {'name': 'Kasoa Corridor', 'type': 'Greater Accra Metro Border Unit', 'status': 'High Alert', 'officers': 15, 'cases': 80, 'badge': 'Metro Outpost', 'x': 475.0, 'y': 825.0},
    ],
    'Eastern': [
        {'name': 'Koforidua', 'type': 'Regional Headquarters Command', 'status': 'Command HQ', 'officers': 18, 'cases': 135, 'badge': 'Regional HQ', 'x': 453.0, 'y': 715.0},
        {'name': 'Nkawkaw', 'type': 'Kwahu Escarpment Field Command', 'status': 'Active Patrol', 'officers': 12, 'cases': 70, 'badge': 'Highland Base', 'x': 415.0, 'y': 660.0},
        {'name': 'Suhum', 'type': 'National Highway Interception Post', 'status': 'Active Patrol', 'officers': 11, 'cases': 55, 'badge': 'Highway Base', 'x': 445.0, 'y': 745.0},
        {'name': 'Akosombo', 'type': 'Volta Lake Basin & Hydro Dam Security', 'status': 'Surveillance', 'officers': 14, 'cases': 42, 'badge': 'Lake Security', 'x': 545.0, 'y': 715.0},
        {'name': 'Nsawam', 'type': 'Southern Basin Gateway Post', 'status': 'High Alert', 'officers': 13, 'cases': 68, 'badge': 'Gateway Base', 'x': 468.0, 'y': 775.0},
    ],
    'Volta': [
        {'name': 'Ho', 'type': 'Regional Headquarters Command', 'status': 'Command HQ', 'officers': 20, 'cases': 110, 'badge': 'Regional HQ', 'x': 620.0, 'y': 650.0},
        {'name': 'Aflao', 'type': 'Togo Border Command & Customs Post', 'status': 'High Alert', 'officers': 28, 'cases': 185, 'badge': 'Border Command', 'x': 695.0, 'y': 735.0},
        {'name': 'Hohoe', 'type': 'Northern Frontier Foothill Station', 'status': 'Active Patrol', 'officers': 12, 'cases': 48, 'badge': 'Frontier Unit', 'x': 615.0, 'y': 600.0},
        {'name': 'Kpando', 'type': 'Lake Volta Ferry Terminal Station', 'status': 'Surveillance', 'officers': 10, 'cases': 35, 'badge': 'Ferry Watch', 'x': 570.0, 'y': 635.0},
        {'name': 'Sogakope', 'type': 'Lower Volta Bridge Intercept Post', 'status': 'Active Patrol', 'officers': 11, 'cases': 40, 'badge': 'Bridge Post', 'x': 625.0, 'y': 765.0},
    ],
    'Northern': [
        {'name': 'Tamale Central', 'type': 'Northern Sector Supreme Command', 'status': 'Command HQ', 'officers': 22, 'cases': 195, 'badge': 'Northern HQ', 'x': 465.0, 'y': 285.0},
        {'name': 'Yendi', 'type': 'Eastern Corridor Command Station', 'status': 'Active Patrol', 'officers': 15, 'cases': 78, 'badge': 'Corridor Base', 'x': 550.0, 'y': 280.0},
        {'name': 'Savelugu', 'type': 'Trans-Sahara Highway Station', 'status': 'Surveillance', 'officers': 11, 'cases': 45, 'badge': 'Highway Base', 'x': 450.0, 'y': 245.0},
        {'name': 'Bimbilla', 'type': 'Oti Basin Border Frontier Post', 'status': 'High Alert', 'officers': 13, 'cases': 62, 'badge': 'Border Post', 'x': 590.0, 'y': 345.0},
    ],
    'Upper East': [
        {'name': 'Bolgatanga', 'type': 'Regional Command Headquarters', 'status': 'Command HQ', 'officers': 14, 'cases': 88, 'badge': 'Regional HQ', 'x': 445.0, 'y': 95.0},
        {'name': 'Paga', 'type': 'Burkina Faso Border Crossing Post', 'status': 'Border Control', 'officers': 20, 'cases': 112, 'badge': 'Border Command', 'x': 415.0, 'y': 65.0},
        {'name': 'Bawku', 'type': 'Tri-Border Tactical Security Post', 'status': 'High Alert', 'officers': 18, 'cases': 95, 'badge': 'Tactical Post', 'x': 525.0, 'y': 75.0},
        {'name': 'Navrongo', 'type': 'Western Savanna Field Station', 'status': 'Normal Patrol', 'officers': 10, 'cases': 32, 'badge': 'Savanna Post', 'x': 380.0, 'y': 90.0},
    ],
    'Upper West': [
        {'name': 'Wa Central', 'type': 'Regional Headquarters Command', 'status': 'Command HQ', 'officers': 12, 'cases': 72, 'badge': 'Regional HQ', 'x': 215.0, 'y': 175.0},
        {'name': 'Hamile', 'type': 'Northwest Border Command Post', 'status': 'Border Control', 'officers': 16, 'cases': 65, 'badge': 'Border Hub', 'x': 180.0, 'y': 70.0},
        {'name': 'Tumu', 'type': 'Northeastern Border Corridor Unit', 'status': 'Active Patrol', 'officers': 11, 'cases': 44, 'badge': 'Corridor Unit', 'x': 285.0, 'y': 68.0},
        {'name': 'Lawra', 'type': 'Black Volta River Security Post', 'status': 'Surveillance', 'officers': 9, 'cases': 28, 'badge': 'River Post', 'x': 155.0, 'y': 145.0},
    ],
    'Bono': [
        {'name': 'Sunyani Central', 'type': 'Regional Headquarters Hub', 'status': 'Command HQ', 'officers': 15, 'cases': 95, 'badge': 'Regional HQ', 'x': 210.0, 'y': 480.0},
        {'name': 'Berekum', 'type': 'Western Transit Intercept Station', 'status': 'Active Patrol', 'officers': 11, 'cases': 48, 'badge': 'Transit Post', 'x': 175.0, 'y': 485.0},
        {'name': 'Dormaa Ahenkro', 'type': 'Ivory Coast Western Border Post', 'status': 'Border Control', 'officers': 16, 'cases': 72, 'badge': 'Border Command', 'x': 145.0, 'y': 525.0},
        {'name': 'Wenchi', 'type': 'Agricultural Basin Intercept Station', 'status': 'Surveillance', 'officers': 10, 'cases': 35, 'badge': 'Basin Post', 'x': 245.0, 'y': 445.0},
    ],
    'Bono East': [
        {'name': 'Techiman', 'type': 'Commercial Transit Hub Command', 'status': 'Command HQ', 'officers': 13, 'cases': 85, 'badge': 'Transit HQ', 'x': 325.0, 'y': 485.0},
        {'name': 'Kintampo', 'type': 'National Geographic Center Base', 'status': 'Active Patrol', 'officers': 12, 'cases': 60, 'badge': 'Central Base', 'x': 345.0, 'y': 420.0},
        {'name': 'Atebubu', 'type': 'Eastern Savanna Outpost Station', 'status': 'Normal Patrol', 'officers': 9, 'cases': 32, 'badge': 'Savanna Base', 'x': 430.0, 'y': 500.0},
        {'name': 'Yeji', 'type': 'Lake Volta Ferry Terminal Post', 'status': 'Surveillance', 'officers': 11, 'cases': 45, 'badge': 'Ferry Watch', 'x': 490.0, 'y': 465.0},
    ],
    'Ahafo': [
        {'name': 'Goaso', 'type': 'Regional Headquarters Command', 'status': 'Command HQ', 'officers': 10, 'cases': 46, 'badge': 'Regional HQ', 'x': 210.0, 'y': 635.0},
        {'name': 'Kenyasi', 'type': 'Gold Mining Security Base', 'status': 'Active Patrol', 'officers': 11, 'cases': 38, 'badge': 'Mining Post', 'x': 235.0, 'y': 605.0},
        {'name': 'Bechem', 'type': 'Highway Transit Checkpoint', 'status': 'Surveillance', 'officers': 8, 'cases': 24, 'badge': 'Highway Post', 'x': 255.0, 'y': 585.0},
    ],
    'Western North': [
        {'name': 'Sefwi Wiawso', 'type': 'Regional Headquarters Command', 'status': 'Command HQ', 'officers': 11, 'cases': 65, 'badge': 'Regional HQ', 'x': 155.0, 'y': 730.0},
        {'name': 'Bibiani', 'type': 'Mineral Basin Tactical Station', 'status': 'Active Patrol', 'officers': 12, 'cases': 42, 'badge': 'Mining Unit', 'x': 195.0, 'y': 695.0},
        {'name': 'Enchi', 'type': 'Western Border Buffer Station', 'status': 'High Alert', 'officers': 10, 'cases': 35, 'badge': 'Border Unit', 'x': 130.0, 'y': 790.0},
    ],
    'Oti': [
        {'name': 'Dambai', 'type': 'Regional Headquarters & Port', 'status': 'Command HQ', 'officers': 9, 'cases': 42, 'badge': 'Regional HQ', 'x': 590.0, 'y': 470.0},
        {'name': 'Nkwanta', 'type': 'Northern Frontier Post', 'status': 'High Alert', 'officers': 11, 'cases': 38, 'badge': 'Frontier Unit', 'x': 625.0, 'y': 430.0},
        {'name': 'Jasikan', 'type': 'Southern Mountain Patrol Station', 'status': 'Active Patrol', 'officers': 8, 'cases': 26, 'badge': 'Mountain Base', 'x': 605.0, 'y': 540.0},
    ],
    'Savannah': [
        {'name': 'Damongo', 'type': 'Regional Headquarters Command', 'status': 'Command HQ', 'officers': 11, 'cases': 58, 'badge': 'Regional HQ', 'x': 395.0, 'y': 375.0},
        {'name': 'Bole', 'type': 'Western Savanna Highway Station', 'status': 'Active Patrol', 'officers': 12, 'cases': 45, 'badge': 'Highway Base', 'x': 210.0, 'y': 335.0},
        {'name': 'Salaga', 'type': 'Historic Transit Crossroad Base', 'status': 'Normal Patrol', 'officers': 9, 'cases': 28, 'badge': 'Inland Post', 'x': 490.0, 'y': 375.0},
    ],
    'North East': [
        {'name': 'Nalerigu', 'type': 'Regional Headquarters Command', 'status': 'Command HQ', 'officers': 8, 'cases': 38, 'badge': 'Regional HQ', 'x': 475.0, 'y': 155.0},
        {'name': 'Walewale', 'type': 'Central Transit Highway Station', 'status': 'Active Patrol', 'officers': 10, 'cases': 42, 'badge': 'Highway Base', 'x': 410.0, 'y': 185.0},
        {'name': 'Bunkpurugu', 'type': 'Eastern Border Outpost', 'status': 'High Alert', 'officers': 11, 'cases': 36, 'badge': 'Border Unit', 'x': 570.0, 'y': 145.0},
    ]
}

print(f"Loaded bounds for {len(region_bounds)} regions.")
print(f"Loaded towns for {len(towns_data)} regions.")
