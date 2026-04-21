# SmartNutrition - Régime Relationship Implementation Guide

## Overview
Changed from Many-to-Many (junction table) relationship to One-to-Many relationship between `dossier_medical` and `regimes`.

## What Changed

### Database Schema
✅ **New Column Added to `dossier_medical`:**
```sql
ALTER TABLE dossier_medical 
ADD COLUMN id_regime INT UNSIGNED NULL AFTER id_utilisateur,
ADD CONSTRAINT fk_dossier_regime FOREIGN KEY (id_regime) REFERENCES regimes(id_regime) ON DELETE SET NULL;
```

**Key Features:**
- Each `dossier_medical` can have ONE primary `regime`
- Foreign key is nullable (ON DELETE SET NULL) - if regime is deleted, dossier remains but regime link is cleared
- Existing data is preserved during migration

### Model Updates

#### 1. **DossierMedical.php** 
Added field: `id_regime`
- New getter: `getIdRegime()`
- New setter: `setIdRegime($id_regime)`
- Constructor now accepts `id_regime` parameter

#### 2. **dossierMedical.controller.php**
New methods added:
- `attachRegime($id_dossier, $id_regime)` - Link regime to dossier
- `getAvailableRegimes()` - Get all regimes for selection dropdown
- `getDossierWithRegime($id_dossier)` - Get dossier with LEFT JOIN to regimes table

Updated methods:
- `add()` - Now includes `id_regime` in INSERT statement
- `update()` - Now includes `id_regime` in UPDATE statement

### API Endpoints

#### **`api/dossier-medical-api.php`**

**New Actions:**
1. **`attachRegime`** (POST)
   - Parameters: `id_dossier`, `id_regime`
   - Links a regime to a dossier
   - Response: `{success: true, message: "...", data: ...}`

2. **`getAvailableRegimes`** (GET)
   - Returns all regimes for dropdown selection
   - Response: `{success: true, data: [{id_regime, nom_regime, type_regime, ...}]}`

3. **`getDossierWithRegime`** (GET)
   - Parameters: `id` (dossier ID)
   - Returns dossier with full regime details via LEFT JOIN
   - Response includes: all dossier fields + regime fields (nom_regime, description, type_regime, etc.)

**Updated Actions:**
- **`add`** - Now includes `id_regime` from POST data
- **`update`** - Now includes `id_regime` from POST data
- **`get`** - Uses `getDossierWithRegime()` to fetch dossier with regime info

### Frontend Updates

#### **`view/frontend/modules/health.html`**

**New HTML Section:**
```html
<!-- Régime Associé -->
<h4>🍽️ Régime Associé</h4>
- Dropdown to select existing regimes
- Display current regime information
- Button to create new regime
```

**New JavaScript Functions:**

1. **`healthLoadAvailableRegimes()`**
   - Fetches all available regimes from API
   - Populates dropdown select element
   - Called when dossier tab is opened

2. **`healthAttachRegime()`**
   - Called when user selects a regime from dropdown
   - Sends attachRegime action to API
   - Refreshes dossier info after attachment

3. **`healthDisplayCurrentRegime(dossier)`**
   - Displays current regime information
   - Shows: nom_regime, type_regime, niveau_difficulte, apport_calorique_moyen
   - Hidden if no regime attached

4. **`healthOpenCreateRegime()`**
   - Placeholder for regime creation modal
   - To be implemented with full form

**Updated Functions:**
- `loadExistingDossier()` 
  - Now stores dossier ID in `window.currentDossierId`
  - Calls `healthLoadAvailableRegimes()`
  - Calls `healthDisplayCurrentRegime()` with dossier data
  - Sets regime select value if dossier has one

## User Flow

### Adding/Modifying a Dossier with Regime

1. **Click "Bien-être" Tab** → Loads dossier data and available regimes
2. **Fill Medical Info** → Group sanguin, weight, height, allergies, etc.
3. **Select Regime** → Choose from dropdown OR create new one
4. **Save Dossier** → Click "Enregistrer le dossier"
5. **View Current Regime** → Display shows selected regime details

### Creating New Regime
- Click "➕ Créer un nouveau régime"
- (UI to be implemented in next phase)
- After creation, regime automatically selected for current dossier

## SQL Migration File

Location: `database/migration_001_regime_relationship.sql`

Run this in your database:
```sql
ALTER TABLE dossier_medical 
ADD COLUMN id_regime INT UNSIGNED NULL AFTER id_utilisateur,
ADD CONSTRAINT fk_dossier_regime FOREIGN KEY (id_regime) REFERENCES regimes(id_regime) ON DELETE SET NULL;
```

## Data Consistency

✅ **Foreign Key Constraints:**
- Regime deletion: Dossier.id_regime → NULL (ON DELETE SET NULL)
- Dossier deletion: Cascades to regimes if it was the only reference (no, regimes are not deleted)

✅ **Data Preservation:**
- Existing dossiers without regime: id_regime = NULL (valid)
- Can add regime anytime after dossier creation

## API Response Examples

### Get Dossier with Regime
```json
{
  "success": true,
  "message": "Dossier avec régime récupéré",
  "data": {
    "id_dossier": 1,
    "id_utilisateur": 1,
    "id_regime": 3,
    "groupe_sanguin": "O+",
    "poids": 75,
    "taille": 180,
    "imc": 23.1,
    "allergie": "Arachides",
    "nom_regime": "Régime Méditerranéen",
    "type_regime": "alimentaire",
    "niveau_difficulte": "modere",
    "apport_calorique_moyen": 2000
  }
}
```

### Get Available Regimes
```json
{
  "success": true,
  "message": "Régimes disponibles récupérés",
  "data": [
    {
      "id_regime": 1,
      "nom_regime": "Régime Kéto",
      "description": "...",
      "type_regime": "perte_de_poids",
      "niveau_difficulte": "avance",
      "apport_calorique_moyen": 1800
    },
    {
      "id_regime": 2,
      "nom_regime": "Régime Vegan",
      "description": "...",
      "type_regime": "alimentaire",
      "niveau_difficulte": "modere",
      "apport_calorique_moyen": 2200
    }
  ]
}
```

## Testing Checklist

- [ ] Run migration SQL in database
- [ ] Open Health module → Dossier tab loads regimes dropdown
- [ ] Select existing regime → Shows regime details
- [ ] Save dossier → id_regime is saved to database
- [ ] Reload page → Regime selection persists
- [ ] Create new regime (placeholder) → Navigates to create form
- [ ] Delete regime → Dossier.id_regime becomes NULL

## Future Enhancements

1. **Régime Création Modal** - Full form to create new regime on-the-fly
2. **Régime Modification** - Edit selected regime details
3. **Multiple Régimes** - Option to track multiple regimes (expand to many-to-many if needed)
4. **Regime History** - Track regime changes over time
5. **Recommendations** - AI-powered regime suggestions based on medical data

## Files Modified

1. ✅ `/Model/DossierMedical.php` - Added id_regime field
2. ✅ `/controller/dossierMedical.controller.php` - Added regime methods, updated add/update
3. ✅ `/api/dossier-medical-api.php` - Added new endpoints
4. ✅ `/view/frontend/modules/health.html` - Added regime selection UI & JS functions
5. ✅ `/database/migration_001_regime_relationship.sql` - Migration script

## Support

For questions or issues:
1. Check database constraints: `SHOW CREATE TABLE dossier_medical;`
2. Verify foreign keys: `SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME='dossier_medical';`
3. Test API endpoint: `GET /api/dossier-medical-api.php?action=getAvailableRegimes`
