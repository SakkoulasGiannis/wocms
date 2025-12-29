# 🧪 Πώς να ελέγξεις το Full Page Cache

## Μέθοδος 1: Με το Command (Πιο Εύκολο)

### Βήμα 1: Καθάρισε το cache
```bash
php artisan cache:clear
```

### Βήμα 2: Δες τι είναι cached (πριν την επίσκεψη)
```bash
php artisan cache:show-pages
```
Θα δεις: **"Enabled but not cached yet"** για όλες τις blog σελίδες

### Βήμα 3: Επίσκεψη σε μια σελίδα
Άνοιξε το browser και πήγαινε σε:
```
http://cms.ddev.site/blog/veltiistopoiisi-seo-gia-tin-istoselida-sas
```
ή
```
https://wocms.weborange.gr/blog/veltiistopoiisi-seo-gia-tin-istoselida-sas
```

### Βήμα 4: Δες ξανά τι είναι cached (μετά την επίσκεψη)
```bash
php artisan cache:show-pages
```
Θα δεις: **"✅ Cached Pages"** στο table με την σελίδα που επισκέφτηκες!

---

## Μέθοδος 2: Με τα Logs (Πιο Προχωρημένο)

### Βήμα 1: Άνοιξε τα logs σε ένα terminal
```bash
tail -f storage/logs/laravel.log | grep "CACHE"
```

### Βήμα 2: Επίσκεψη σε μια σελίδα (σε άλλο terminal ή browser)
Πήγαινε σε: http://cms.ddev.site/blog/veltiistopoiisi-seo-gia-tin-istoselida-sas

### Βήμα 3: Δες τα logs
Θα δεις:
```
❌ CACHE MISS: /blog/veltiistopoiisi-seo-gia-tin-istoselida-sas (generating and caching for 3600s)
```

### Βήμα 4: Κάνε refresh τη σελίδα
Πάτησε Ctrl+R ή F5

### Βήμα 5: Δες τα logs ξανά
Θα δεις:
```
✅ CACHE HIT: /blog/veltiistopoiisi-seo-gia-tin-istoselida-sas (serving from cache)
```

---

## Μέθοδος 3: Με Browser DevTools (Ταχύτητα)

### Βήμα 1: Άνοιξε DevTools
Πατάω F12 στο browser → Tab "Network"

### Βήμα 2: Επίσκεψη σε σελίδα (1η φορά - χωρίς cache)
Πήγαινε σε blog σελίδα
Δες το **response time**: ~300-800ms

### Βήμα 3: Refresh (2η φορά - με cache)
Πάτα Ctrl+R
Δες το **response time**: ~50-150ms (πολύ πιο γρήγορα!)

---

## Επιπλέον Commands

### Δες όλες τις cached σελίδες
```bash
php artisan cache:show-pages
```

### Καθάρισε όλο το cache
```bash
php artisan cache:clear
```

### Καθάρισε μόνο το cache μιας σελίδας (από το admin)
Πήγαινε στο admin → Πάτα το κουμπί "Clear Cache" πάνω δεξιά

---

## Αν δεν λειτουργεί

1. **Έλεγξε ότι το caching είναι enabled στο template:**
   - Admin → Templates → Edit "Blog"
   - Δες στο "Performance & Caching" → "Enable Full Page Caching" ✓

2. **Έλεγξε τα logs για errors:**
   ```bash
   tail -20 storage/logs/laravel.log
   ```

3. **Έλεγξε ότι η σελίδα φορτώνει κανονικά (όχι 503/500 error)**
