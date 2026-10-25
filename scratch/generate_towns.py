import json, re

# Load regions
with open('scratch/ghana_regions_svg_optimized.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

# Define accurate towns and stations for all 16 Ghana regions with calculated SVG coordinates
# Coordinates are positioned within each region's bounding box
towns_data = {
    'Greater Accra': [
        {'name': 'Accra Central', 'type': 'National Headquarters', 'status': 'Command HQ', 'officers': 46, 'cases': 520, 'dockets': 'Active HQ', 'x': 532.0, 'y': 786.0},
        {'name': 'Tema Port', 'type': 'Seaport & Maritime Customs', 'status': 'High Alert', 'officers': 32, 'cases': 285, 'dockets': 'Port Control', 'x': 565.0, 'y': 782.0},
        {'name': 'Madina', 'type': 'Northern Sector Intake', 'status': 'Active Patrol', 'officers': 18, 'cases': 142, 'dockets': 'Sub-Station', 'x': 536.0, 'y': 768.0},
        {'name': 'Adenta', 'type': 'Aviation Corridor Checkpoint', 'status': 'Surveillance', 'officers': 14, 'cases': 98, 'dockets': 'Highway Post', 'x': 548.0, 'y': 762.0},
        {'name': 'Ashaiman', 'type': 'Transit & Interception Base', 'status': 'Interception', 'officers': 22, 'cases': 115, 'dockets': 'Tactical Unit', 'x': 562.0, 'y': 774.0},
        {'name': 'Prampram', 'type': 'Littoral Coastal Radar Post', 'status': 'Monitoring', 'officers': 10, 'cases': 48, 'dockets': 'Coastal Radar', 'x': 588.0, 'y': 772.0},
        {'name': 'Ada Foah', 'type': 'Volta Estuary Riverine Post', 'status': 'Active Patrol', 'officers': 12, 'cases': 40, 'dockets': 'River Patrol', 'x': 616.0, 'y': 764.0},
    ],
    'Ashanti': [
        {'name': 'Kumasi Central', 'type': 'Regional Command HQ', 'status': 'Command HQ', 'officers': 32, 'cases': 340, 'dockets': 'Regional HQ', 'x': 354.5, 'y': 668.0},
        {'name': 'Obuasi', 'type': 'Goldfield Basin Operations', 'status': 'High Alert', 'officers': 18, 'cases': 120, 'dockets': 'Mining Post', 'x': 310.0, 'y': 730.0},
        {'name': 'Konongo', 'type': 'Eastern Highway Checkpoint', 'status': 'Active Patrol', 'officers': 14, 'cases': 74, 'dockets': 'Highway Post', 'x': 415.0, 'y': 665.0},
        {'name': 'Mampong', 'type': 'Northern Escarpment Base', 'status': 'Normal Patrol', 'officers': 12, 'cases': 62, 'dockets': 'Field Unit', 'x': 370.0, 'y': 590.0},
        {'name': 'Bekwai', 'type': 'Southern Transit Intercept', 'status': 'Surveillance', 'officers': 10, 'cases': 39, 'dockets': 'Transit Post', 'x': 335.0, 'y': 705.0},
        {'name': 'Ejisu', 'type': 'Logistics & Intercept Station', 'status': 'Active Patrol', 'officers': 11, 'cases': 45, 'dockets': 'Intake Post', 'x': 382.0, 'y': 658.0},
    ],
    'Western': [
        {'name': 'Sekondi-Takoradi', 'type': 'Twin-City Maritime Base', 'status': 'Command HQ', 'officers': 24, 'cases': 210, 'dockets': 'Maritime HQ', 'x': 275.0, 'y': 910.0},
        {'name': 'Tarkwa', 'type': 'Mineral Belt Intelligence', 'status': 'High Alert', 'officers': 18, 'cases': 130, 'dockets': 'Mining Unit', 'x': 225.0, 'y': 845.0},
        {'name': 'Elubo', 'type': 'Ivory Coast Border Command', 'status': 'Border Control', 'officers': 20, 'cases': 145, 'dockets': 'Border Hub', 'x': 135.0, 'y': 868.0},
        {'name': 'Axim', 'type': 'Deep-Sea Offshore Watch', 'status': 'Active Patrol', 'officers': 12, 'cases': 55, 'dockets': 'Coastal Watch', 'x': 200.0, 'y': 885.0},
        {'name': 'Bogoso', 'type': 'Inland Transit Checkpoint', 'status': 'Normal Patrol', 'officers': 10, 'cases': 42, 'dockets': 'Field Station', 'x': 210.0, 'y': 800.0},
    ],
    'Central': [
        {'name': 'Cape Coast', 'type': 'Regional Headquarters', 'status': 'Command HQ', 'officers': 16, 'cases': 120, 'dockets': 'Regional HQ', 'x': 355.0, 'y': 870.0},
        {'name': 'Winneba', 'type': 'Coastal Highway Command', 'status': 'Active Patrol', 'officers': 12, 'cases': 65, 'dockets': 'Highway Base', 'x': 435.0, 'y': 848.0},
        {'name': 'Mankessim', 'type': 'Market & Transit Intercept', 'status': 'Surveillance', 'officers': 10, 'cases': 48, 'dockets': 'Transit Post', 'x': 385.0, 'y': 862.0},
        {'name': 'Elmina', 'type': 'Fisheries Maritime Station', 'status': 'Normal Patrol', 'officers': 9, 'cases': 32, 'dockets': 'Coastal Post', 'x': 338.0, 'y': 876.0},
        {'name': 'Kasoa Corridor', 'type': 'Metro Border Intercept', 'status': 'High Alert', 'officers': 15, 'cases': 80, 'dockets': 'Metro Unit', 'x': 475.0, 'y': 825.0},
    ],
    'Eastern': [
        {'name': 'Koforidua', 'type': 'Regional Headquarters', 'status': 'Command HQ', 'officers': 18, 'cases': 135, 'dockets': 'Regional HQ', 'x': 453.0, 'y': 715.0},
        {'name': 'Nkawkaw', 'type': 'Kwahu Ridge Operations', 'status': 'Active Patrol', 'officers': 12, 'cases': 70, 'dockets': 'Highland Base', 'x': 415.0, 'y': 660.0},
        {'name': 'Suhum', 'type': 'Expressway Transit Post', 'status': 'Active Patrol', 'officers': 11, 'cases': 55, 'dockets': 'Highway Post', 'x': 445.0, 'y': 745.0},
        {'name': 'Akosombo', 'type': 'Hydro Dam & Lake Basin', 'status': 'Surveillance', 'officers': 14, 'cases': 42, 'dockets': 'Dam Security', 'x': 545.0, 'y': 715.0},
        {'name': 'Nsawam', 'type': 'Southern Inflow Checkpoint', 'status': 'High Alert', 'officers': 13, 'cases': 68, 'dockets': 'Interception Post', 'x': 468.0, 'y': 775.0},
    ],
    'Volta': [
        {'name': 'Ho', 'type': 'Regional Headquarters', 'status': 'Command HQ', 'officers': 20, 'cases': 110, 'dockets': 'Regional HQ', 'x': 620.0, 'y': 650.0},
        {'name': 'Aflao', 'type': 'Togo Border Command Hub', 'status': 'High Alert', 'officers': 28, 'cases': 185, 'dockets': 'Border Command', 'x': 695.0, 'y': 735.0},
        {'name': 'Hohoe', 'type': 'Northern Mountain Frontier', 'status': 'Active Patrol', 'officers': 12, 'cases': 48, 'dockets': 'Frontier Unit', 'x': 615.0, 'y': 600.0},
        {'name': 'Kpando', 'type': 'Lake Volta Ferry Terminal', 'status': 'Surveillance', 'officers': 10, 'cases': 35, 'dockets': 'Ferry Watch', 'x': 570.0, 'y': 635.0},
        {'name': 'Sogakope', 'type': 'Lower Volta Bridge Post', 'status': 'Active Patrol', 'officers': 11, 'cases': 40, 'dockets': 'Bridge Post', 'x': 625.0, 'y': 765.0},
    ],
    'Northern': [
        {'name': 'Tamale Central', 'type': 'Northern Sector Supreme Command', 'status': 'Command HQ', 'officers': 22, 'cases': 195, 'dockets': 'Regional HQ', 'x': 465.0, 'y': 285.0},
        {'name': 'Yendi', 'type': 'Eastern Corridor Hub', 'status': 'Active Patrol', 'officers': 15, 'cases': 78, 'dockets': 'Eastern Base', 'x': 550.0, 'y': 280.0},
        {'name': 'Savelugu', 'type': 'Trans-Sahara Highway Post', 'status': 'Surveillance', 'officers': 11, 'cases': 45, 'dockets': 'Highway Post', 'x': 450.0, 'y': 245.0},
        {'name': 'Bimbilla', 'type': 'Southern Border Frontier', 'status': 'High Alert', 'officers': 13, 'cases': 62, 'dockets': 'Border Unit', 'x': 590.0, 'y': 345.0},
    ],
    'Upper East': [
        {'name': 'Bolgatanga', 'type': 'Regional Command HQ', 'status': 'Command HQ', 'officers': 14, 'cases': 88, 'dockets': 'Regional HQ', 'x': 445.0, 'y': 95.0},
        {'name': 'Paga', 'type': 'Burkina Faso Border Station', 'status': 'Border Control', 'officers': 20, 'cases': 112, 'dockets': 'Main Border Post', 'x': 415.0, 'y': 65.0},
        {'name': 'Bawku', 'type': 'Tri-Border Tactical Post', 'status': 'High Alert', 'officers': 18, 'cases': 95, 'dockets': 'Tactical Unit', 'x': 525.0, 'y': 75.0},
        {'name': 'Navrongo', 'type': 'Western Savanna Post', 'status': 'Normal Patrol', 'officers': 10, 'cases': 32, 'dockets': 'Field Station', 'x': 380.0, 'y': 90.0},
    ],
    'Upper West': [
        {'name': 'Wa Central', 'type': 'Regional Headquarters', 'status': 'Command HQ', 'officers': 12, 'cases': 72, 'dockets': 'Regional HQ', 'x': 215.0, 'y': 175.0},
        {'name': 'Hamile', 'type': 'Northwest Border Station', 'status': 'Border Control', 'officers': 16, 'cases': 65, 'dockets': 'Border Hub', 'x': 180.0, 'y': 70.0},
        {'name': 'Tumu', 'type': 'Northeastern Border Corridor', 'status': 'Active Patrol', 'officers': 11, 'cases': 44, 'dockets': 'Corridor Post', 'x': 285.0, 'y': 68.0},
        {'name': 'Lawra', 'type': 'Black Volta River Post', 'status': 'Surveillance', 'officers': 9, 'cases': 28, 'dockets': 'River Watch', 'x': 155.0, 'y': 145.0},
    ],
    'Bono': [
        {'name': 'Sunyani Central', 'type': 'Regional Headquarters', 'status': 'Command HQ', 'officers': 15, 'cases': 95, 'dockets': 'Regional HQ', 'x': 210.0, 'y': 480.0},
        {'name': 'Berekum', 'type': 'Western Transit Station', 'status': 'Active Patrol', 'officers': 11, 'cases': 48, 'dockets': 'Field Station', 'x': 175.0, 'y': 485.0},
        {'name': 'Dormaa Ahenkro', 'type': 'Ivory Coast Border Sector', 'status': 'Border Control', 'officers': 16, 'cases': 72, 'dockets': 'Border Command', 'x': 145.0, 'y': 525.0},
        {'name': 'Wenchi', 'type': 'Agricultural Basin Intercept', 'status': 'Surveillance', 'officers': 10, 'cases': 35, 'dockets': 'Intercept Unit', 'x': 245.0, 'y': 445.0},
    ],
    'Bono East': [
        {'name': 'Techiman', 'type': 'Commercial Transit Hub HQ', 'status': 'Command HQ', 'officers': 13, 'cases': 85, 'dockets': 'Market Command', 'x': 325.0, 'y': 485.0},
        {'name': 'Kintampo', 'type': 'National Geographic Center Post', 'status': 'Active Patrol', 'officers': 12, 'cases': 60, 'dockets': 'Highway Base', 'x': 345.0, 'y': 420.0},
        {'name': 'Atebubu', 'type': 'Eastern Savanna Outpost', 'status': 'Normal Patrol', 'officers': 9, 'cases': 32, 'dockets': 'Savanna Post', 'x': 430.0, 'y': 500.0},
        {'name': 'Yeji', 'type': 'Lake Volta Ferry Crossing', 'status': 'Surveillance', 'officers': 11, 'cases': 45, 'dockets': 'Ferry Terminal', 'x': 490.0, 'y': 465.0},
    ],
    'Ahafo': [
        {'name': 'Goaso', 'type': 'Regional Headquarters', 'status': 'Command HQ', 'officers': 10, 'cases': 46, 'dockets': 'Regional HQ', 'x': 210.0, 'y': 635.0},
        {'name': 'Kenyasi', 'type': 'Mining Area Station', 'status': 'Active Patrol', 'officers': 11, 'cases': 38, 'dockets': 'Mining Post', 'x': 235.0, 'y': 605.0},
        {'name': 'Bechem', 'type': 'Transit Corridor Checkpoint', 'status': 'Surveillance', 'officers': 8, 'cases': 24, 'dockets': 'Transit Post', 'x': 255.0, 'y': 585.0},
    ],
    'Western North': [
        {'name': 'Sefwi Wiawso', 'type': 'Regional Headquarters', 'status': 'Command HQ', 'officers': 11, 'cases': 65, 'dockets': 'Regional HQ', 'x': 155.0, 'y': 730.0},
        {'name': 'Bibiani', 'type': 'Gold Mining Security Base', 'status': 'Active Patrol', 'officers': 12, 'cases': 42, 'dockets': 'Mining Unit', 'x': 195.0, 'y': 695.0},
        {'name': 'Enchi', 'type': 'Border Buffer Station', 'status': 'High Alert', 'officers': 10, 'cases': 35, 'dockets': 'Border Base', 'x': 130.0, 'y': 790.0},
    ],
    'Oti': [
        {'name': 'Dambai', 'type': 'Regional Headquarters', 'status': 'Command HQ', 'officers': 9, 'cases': 42, 'dockets': 'Regional HQ', 'x': 590.0, 'y': 470.0},
        {'name': 'Nkwanta', 'type': 'Northern Frontier Post', 'status': 'High Alert', 'officers': 11, 'cases': 38, 'dockets': 'Frontier Unit', 'x': 625.0, 'y': 430.0},
        {'name': 'Jasikan', 'type': 'Southern Mountain Base', 'status': 'Active Patrol', 'officers': 8, 'cases': 26, 'dockets': 'Field Base', 'x': 605.0, 'y': 540.0},
    ],
    'Savannah': [
        {'name': 'Damongo', 'type': 'Regional Headquarters', 'status': 'Command HQ', 'officers': 11, 'cases': 58, 'dockets': 'Regional HQ', 'x': 395.0, 'y': 375.0},
        {'name': 'Bole', 'type': 'Western Border Highway Post', 'status': 'Active Patrol', 'officers': 12, 'cases': 45, 'dockets': 'Highway Base', 'x': 210.0, 'y': 335.0},
        {'name': 'Salaga', 'type': 'Historic Transit Base', 'status': 'Normal Patrol', 'officers': 9, 'cases': 28, 'dockets': 'Inland Post', 'x': 490.0, 'y': 375.0},
    ],
    'North East': [
        {'name': 'Nalerigu', 'type': 'Regional Headquarters', 'status': 'Command HQ', 'officers': 8, 'cases': 38, 'dockets': 'Regional HQ', 'x': 475.0, 'y': 155.0},
        {'name': 'Walewale', 'type': 'Tamale-Bolgatanga Transit', 'status': 'Active Patrol', 'officers': 10, 'cases': 42, 'dockets': 'Highway Station', 'x': 410.0, 'y': 185.0},
        {'name': 'Bunkpurugu', 'type': 'Eastern Border Post', 'status': 'High Alert', 'officers': 11, 'cases': 36, 'dockets': 'Border Unit', 'x': 570.0, 'y': 145.0},
    ]
}

# Print summary
print(f"Total regions mapped: {len(towns_data)}")
for r, ts in towns_data.items():
    print(f"  {r}: {len(ts)} towns")
