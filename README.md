# AGRISOFT (XAMPP) – Aplicació web per a explotació fructera

Tecnologies: **PHP 8+**, **MySQL**, **HTML/CSS/JS** (XAMPP).

## Instal·lació ràpida
1) Copia la carpeta `agrisoft_v2` dins `C:\xampp\htdocs\agrisoft`
   - (o renomena-la a `agrisoft` si vols)
2) Engega Apache + MySQL a XAMPP
3) Obre: `http://localhost/agrisoft/public/install.php`
4) Login:  
   - Usuari: `admin@agrisoft.local`  
   - Contrasenya: `admin123`

## Mòduls (segons requisits del document)
- Parcel·les amb GPS + sectors (subparcel·les) + files (arbres) + mapa (Leaflet placeholder)
- Catàleg de cultius i varietats + assignacions i històric
- Tractaments: planificació + registre per parcel·la/sector/fila, càlcul de dosi i traçabilitat (vista)
- Observacions de plagues (monitorització)
- Seguiment nutricional (anàlisi sòl/fulla)
- Productes fitosanitaris: stock + caducitat + alertes
- Cosecha: registre per campanya + lots fins client + QR placeholder
- Personal: treballadors, documents i venciments, certificacions, parts d’hores i costos
- Tasques amb calendari simple
- Alertes i notificacions (bústia interna + email opcional)
- Reporting (taules + gràfiques Chart.js placeholder)

> Leaflet / Chart.js / QR: fitxers “vendor” són placeholders per treballar offline.  
> Substitueix-los per les llibreries reals i tindràs mapa/gràfiques/QR funcionals.
