<div align="center">

<img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white"/>
<img src="https://img.shields.io/badge/MySQL-MariaDB_10.4-4479A1?style=for-the-badge&logo=mariadb&logoColor=white"/>
<img src="https://img.shields.io/badge/Python-3.x-3776AB?style=for-the-badge&logo=python&logoColor=white"/>
<img src="https://img.shields.io/badge/WebSocket-Ratchet-00B4D8?style=for-the-badge"/>
<img src="https://img.shields.io/badge/AI-Claude_Anthropic-D97706?style=for-the-badge"/>
<img src="https://img.shields.io/badge/License-MIT-22C55E?style=for-the-badge"/>

# 🌿 SmartNutrition — GaiaLumen

### Plateforme intelligente de nutrition, santé, planning et bien-être

**Projet Esprit 2A19 · Année académique 2025–2026**

[🚀 Demo Live](#) · [📖 Documentation](#architecture-technique) · [🐛 Issues](https://github.com/kosay-labidi/Esprit-PW-2A19-2526-SmartNutrition/issues) · [📦 Releases](#)

---

</div>

## 📋 Table des Matières

- [🎯 Vue d'ensemble](#-vue-densemble)
- [✨ Fonctionnalités Complètes](#-fonctionnalités-complètes)
- [🏗️ Architecture Technique](#%EF%B8%8F-architecture-technique)
- [📂 Structure du Projet](#-structure-du-projet)
- [🗄️ Schéma de Base de Données](#%EF%B8%8F-schéma-de-base-de-données)
- [⚙️ Installation & Configuration](#%EF%B8%8F-installation--configuration)
- [🤖 Intégrations IA & Services Externes](#-intégrations-ia--services-externes)
- [👥 Gestion des Utilisateurs](#-gestion-des-utilisateurs)
- [🥗 Gestion Alimentaire & Repas](#-gestion-alimentaire--repas)
- [🏥 Dossier Médical & Régimes](#-dossier-médical--régimes)
- [📅 Planning & Sport/Sommeil](#-planning--sportsommeil)
- [🏆 Défis & Challenges](#-défis--challenges)
- [🎪 Événements & Participations](#-événements--participations)
- [💬 Chat Temps Réel](#-chat-temps-réel)
- [🛡️ Sécurité & Authentification](#%EF%B8%8F-sécurité--authentification)
- [🎨 Interface Frontend / Backend](#-interface-frontend--backend)
- [📊 Plan d'Animation Complet](#-plan-danimation-complet)
- [🧪 Tests & Qualité](#-tests--qualité)
- [🤝 Contribution](#-contribution)
- [👨‍💻 Équipe](#-équipe)

---

## 🎯 Vue d'ensemble

**SmartNutrition** est une application web full-stack développée dans le cadre du module *Projet Web* de l'**ESPRIT School of Engineering** (promotion 2A19, 2025–2026). Elle offre une plateforme complète de gestion du bien-être personnel intégrant :

- 🧠 **Intelligence Artificielle** (Anthropic Claude, modèles ML Python)
- 🥗 **Suivi nutritionnel** personnalisé avec base alimentaire enrichie
- 🏥 **Dossier médical numérique** avec gestion des régimes
- 📅 **Planning sportif intelligent** avec météo contextuelle
- 🏆 **Système de défis gamifiés** avec paiement intégré
- 💬 **Messagerie temps réel** via WebSocket (Ratchet)
- 🌐 **Traduction multilingue** de contenus
- 📊 **Tableau de bord analytique** admin avec prédictions ML

> **Stack :** PHP 8.2 · MariaDB 10.4 · Python 3.x · Ratchet WebSocket · Symfony Components · Anthropic Claude AI · PHPMailer · Twilio SDK · QR Code Generator · Google OAuth · Twitter OAuth

---

## ✨ Fonctionnalités Complètes

| Module | Fonctionnalités clés | IA intégrée |
|--------|---------------------|-------------|
| 👤 Utilisateurs | Inscription, Login, OAuth, Profil, Avatar, Amis | Génération avatar IA |
| 🥗 Aliments | CRUD, Nutritional data, Filtrage, Statistiques | Recommandations IA |
| 🍽️ Repas | CRUD, Calcul macro, Historique, Aliments liés | Analyse nutritionnelle |
| 🏥 Santé | Dossier médical, Régimes, Export PDF | Diagnostic IA |
| 📅 Planning | Planning sportif/sommeil, Demandes, IA Coach | Génération auto IA |
| 🏆 Défis | CRUD, Paiement, Likes, Streaks, Vidéos Live | Génération défi IA |
| 🎪 Événements | CRUD, Participation, QR Code, Carte | — |
| 💬 Chat | Temps réel WS, Fichiers, Vidéos, Notifications | Chatbot IA |
| 👫 Amis | Requêtes, Acceptation, Réseau social | — |
| 📊 Admin | Dashboard, Analytics, Prédictions ML | Prédictions ML Python |

---

## 🏗️ Architecture Technique

```
SmartNutrition (MVC Custom)
├── 🎮 Controller Layer       → Logique métier PHP (PDO)
├── 🗃️ Model Layer            → Entités & accès BDD
├── 🖥️ View Layer             → Frontend HTML/JS/CSS + Backend Admin PHP
├── 🔌 API Layer              → Endpoints JSON REST
├── 🤖 AI Services            → Python FastAPI + Claude Anthropic
└── 🔁 WebSocket Server       → Ratchet (chat temps réel)
```

### Composants & Librairies

| Composant | Version | Rôle |
|-----------|---------|------|
| `cboden/ratchet` | ^0.4 | Serveur WebSocket temps réel |
| `phpmailer/phpmailer` | * | Envoi d'emails (SMTP) |
| `endroid/qr-code` | ^6.0 | Génération QR Code participations |
| `bacon/bacon-qr-code` | — | Backend QR Code |
| `symfony/http-foundation` | ^6.4 | Gestion requêtes/sessions HTTP |
| `symfony/routing` | — | Routage URL |
| `react/event-loop` | ^1.5 | Boucle événementielle async |
| `guzzlehttp/psr7` | — | Messages PSR-7 HTTP |
| `twilio/sdk` | — | SMS, notifications, Chat |
| Python `FastAPI` | — | API ML & IA Python |
| Anthropic Claude | API | Génération contenu & chatbot |

---

## 📂 Structure du Projet

```
SmartNutrition/
│
├── 📁 Model/                          # Entités & accès base de données
│   ├── User.php                       # Utilisateur (auth, profil, rôles)
│   ├── aliment.php                    # Aliment nutritionnel
│   ├── repas_model.php                # Repas & composition
│   ├── DossierMedical.php             # Dossier médical patient
│   ├── Regime.php                     # Régimes alimentaires
│   ├── Planning.php                   # Planning sportif/sommeil
│   ├── Demandeplanning.php            # Demandes de planning
│   ├── Sportsommeil.php               # Suivi sport & sommeil
│   ├── Challenge.php                  # Défis/challenges
│   ├── Participant.php                # Participants aux défis
│   ├── Participation.php              # Participations événements
│   ├── Evenement.php                  # Événements
│   ├── Event.php                      # Events frontend
│   ├── Meal.php                       # Repas (alias frontend)
│   └── Translate.php                  # Traduction multilingue
│
├── 📁 controller/                     # Contrôleurs métier
│   ├── user.controller.php            # Auth, CRUD utilisateurs
│   ├── alimentcontroller.php          # Gestion aliments
│   ├── repascontroller.php            # Gestion repas
│   ├── dossierMedical.controller.php  # Dossier médical
│   ├── regime.controller.php          # Régimes
│   ├── planning.controller.php        # Planning
│   ├── Demandeplanning.controller.php # Demandes planning
│   ├── Sportsommeil.controller.php    # Sport & sommeil
│   ├── challenge.controller.php       # Défis
│   ├── participant.controller.php     # Gestion participants
│   ├── ParticipationController.php    # Participations
│   ├── EvenementController.php        # Événements
│   ├── event.controller.php           # Events
│   ├── meal.controller.php            # Repas frontend
│   ├── ChatbotController.php          # Chatbot IA
│   ├── MedicalApiController.php       # API médicale externe
│   ├── NotificationController.php     # Notifications
│   ├── paiementDefi.controller.php    # Paiement défis
│   ├── translate_controller.php       # Traduction
│   └── Passwordreset.controller.php   # Réinitialisation MDP
│
├── 📁 helpers/                        # Fonctions utilitaires
│   ├── aliment_helpers.php
│   ├── repas_helpers.php
│   └── auth_user.php
│
├── 📁 services/                       # Services transversaux
│   ├── EmailService.php               # Service email (PHPMailer)
│   └── QrCodeService.php              # Service QR Code
│
├── 📁 api_composer/                   # Microservices Python IA
│   ├── main.py                        # Point d'entrée FastAPI
│   ├── medical_client.py              # Client API médicale
│   ├── retrain_model.py               # Ré-entraînement modèles ML
│   ├── sentiment_loader.py            # Analyse de sentiment
│   ├── email_sender.py                # Envoi emails Python
│   └── tcm_herbs.json                 # Base plantes médicinales TCM
│
├── 📁 api/                            # APIs PHP supplémentaires
│   ├── culture_ia.php                 # IA culture nutritionnelle
│   └── recommandation_ia.php          # Recommandations personnalisées IA
│
├── 📁 view/
│   ├── 📁 frontend/                   # Interface utilisateur
│   │   ├── index.html                 # Page d'accueil
│   │   ├── login.html                 # Connexion
│   │   ├── dashboard.html             # Tableau de bord
│   │   ├── friends.html               # Réseau social/amis
│   │   ├── forgot.html                # Mot de passe oublié
│   │   ├── 📁 modules/               # Modules SPA dynamiques
│   │   ├── 📁 challenges/            # Défis frontend
│   │   ├── 📁 planning/              # Planning frontend
│   │   ├── 📁 health/                # Santé frontend
│   │   ├── 📁 meals/                 # Repas frontend
│   │   ├── 📁 events/                # Événements frontend
│   │   ├── 📁 css/                   # Styles
│   │   └── 📁 js/                    # Scripts frontend
│   │
│   └── 📁 backend/                   # Interface administration
│       ├── admin.html                 # Dashboard admin
│       ├── dashboard-admin.html       # Module dashboard
│       ├── face_login.php             # Connexion faciale
│       ├── 📁 modules/               # Modules admin SPA
│       ├── 📁 challenges/            # Admin défis
│       ├── 📁 planning/              # Admin planning
│       ├── 📁 health/                # Admin santé
│       ├── 📁 meals/                 # Admin repas
│       ├── 📁 evenement/             # Admin événements
│       ├── 📁 participation/         # Admin participations
│       ├── 📁 users/                 # Admin utilisateurs
│       ├── 📁 api/                   # Endpoints REST backend
│       │   ├── ai-challenge-generator.php
│       │   ├── ai-coach-chat.php
│       │   ├── ai-progress-coach.php
│       │   ├── anthropic-messages.php
│       │   ├── challenge-analytics-predictions.php
│       │   ├── ml-predict-risk.php
│       │   ├── stats-user-predictions.php
│       │   └── 📁 chat/             # API Chat
│       └── 📁 ws/                    # Serveur WebSocket
│           ├── server.php
│           └── 📁 src/
│               └── ChatServer.php
│
├── config.php                         # Configuration BDD & app
├── config_services.php                # Clés API services externes
├── auth.php                           # Vérification authentification
├── composer.json                      # Dépendances PHP
├── dsgaialumen.sql                    # Schéma base de données
└── .env.example                       # Variables d'environnement
```

---

## 🗄️ Schéma de Base de Données

La base de données **`dsgaialumen`** contient **30 tables** couvrant tous les domaines métier.

### Entités Principales

```sql
-- Utilisateurs & Authentification
utilisateurs          → users, rôles, profils, avatars, statut, OAuth
password_resets       → tokens reset mot de passe
friend_requests       → réseau social (demandes d'amis)
notifications         → système de notifications

-- Alimentation
aliments              → catalogue alimentaire (calories, macros, allergènes, CO2)
repas                 → repas composés par utilisateur
repas_aliments        → composition repas ↔ aliments (many-to-many)

-- Santé
dossier_medical       → dossier médical utilisateur (avec id_regime FK)
regimes               → régimes alimentaires prescrits

-- Planning & Activité
planning              → sessions sport/sommeil planifiées
demandeplanning       → demandes de planning utilisateur
sportsommeil          → journaux sport & sommeil

-- Défis & Gamification
challenge             → défis (payants ou gratuits, collectifs/individuels)
participant           → inscriptions aux défis
challenge_likes       → likes défis (trigger SQL synchronisé)
challenge_ai_summaries→ résumés IA des défis
challenge_live_streams→ lives associés aux défis
challenge_videos      → vidéos liées aux défis
paiement_defi         → transactions paiement (Flouci, Stripe)

-- Événements & Participations
evenement             → événements organisés
participation         → inscriptions événements (avec QR Code)
conversations         → conversations entre participants
conversation_participants → membres de conversations

-- Chat Temps Réel
chat_threads          → fils de discussion
chat_messages         → messages (texte, fichier, vidéo)
chat_attachments      → pièces jointes
chat_notifications    → notifications chat
messages              → messages directs inter-utilisateurs

-- Vues
v_challenge_stats     → statistiques défis (vue SQL)
v_participants_details→ détails participants (vue SQL)
```

### Diagramme Relations Clés

```
utilisateurs ──┬── repas ──── repas_aliments ──── aliments
               ├── dossier_medical ──── regimes
               ├── planning
               ├── demandeplanning
               ├── sportsommeil
               ├── participant ──── challenge
               ├── participation ──── evenement
               ├── friend_requests
               └── chat_messages ──── chat_threads
```

---

## ⚙️ Installation & Configuration

### Prérequis

- PHP **8.2+** avec extensions : `pdo_mysql`, `mbstring`, `gd`, `json`, `curl`, `zip`
- **MariaDB 10.4+** ou MySQL 8.0+
- **Composer** 2.x
- **Python 3.9+** avec `pip`
- **Node.js** (optionnel pour le build d'assets)
- Serveur web : Apache/Nginx avec `mod_rewrite`

### Installation Étape par Étape

**1. Cloner le dépôt**
```bash
git clone https://github.com/kosay-labidi/Esprit-PW-2A19-2526-SmartNutrition.git
cd Esprit-PW-2A19-2526-SmartNutrition
```

**2. Installer les dépendances PHP**
```bash
composer install
```

**3. Installer les dépendances Python**
```bash
cd api_composer
pip install -r requirements.txt
cd ..
```

**4. Configurer la base de données**
```bash
# Créer la base de données
mysql -u root -p -e "CREATE DATABASE dsgaialumen CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# Importer le schéma
mysql -u root -p dsgaialumen < dsgaialumen.sql
```

**5. Configurer l'application**
```bash
# Copier les fichiers d'exemple
cp config.php.example config.php
cp .env.example .env
cp gaia.env.example gaia.env
```

Éditer `config.php` :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'dsgaialumen');
define('DB_USER', 'votre_user');
define('DB_PASS', 'votre_mot_de_passe');
define('BASE_URL', 'http://localhost/SmartNutrition');
```

**6. Configurer les variables d'environnement** (`.env`) :
```ini
# Base de données
DB_HOST=localhost
DB_NAME=dsgaialumen
DB_USER=root
DB_PASS=

# Email SMTP
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre@email.com
MAIL_PASSWORD=app_password
MAIL_FROM_NAME=SmartNutrition

# APIs IA
ANTHROPIC_API_KEY=sk-ant-...
GAIA_API_KEY=...

# OAuth Google
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://localhost/SmartNutrition/view/backend/auth/google-callback.php

# OAuth Twitter
TWITTER_API_KEY=...
TWITTER_API_SECRET=...

# Paiement
FLOUCI_APP_TOKEN=...
STRIPE_SECRET_KEY=sk_...
STRIPE_PUBLISHABLE_KEY=pk_...

# Twilio
TWILIO_ACCOUNT_SID=...
TWILIO_AUTH_TOKEN=...
```

**7. Démarrer le serveur WebSocket**
```bash
php view/backend/ws/server.php
# Le serveur écoute sur ws://localhost:8080
```

**8. Démarrer l'API Python IA**
```bash
cd api_composer
python main.py
# Démarre sur http://localhost:8000
```

**9. Configurer Apache VirtualHost**
```apache
<VirtualHost *:80>
    ServerName smartnutrition.local
    DocumentRoot /var/www/html/SmartNutrition
    <Directory /var/www/html/SmartNutrition>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Accès Initial

| URL | Description |
|-----|-------------|
| `/view/frontend/index.html` | Page d'accueil publique |
| `/view/frontend/login.html` | Connexion utilisateur |
| `/view/frontend/register.html` | Inscription |
| `/view/backend/admin.html` | Interface administration |
| `/view/backend/face_login.php` | Connexion par reconnaissance faciale |

**Compte administrateur par défaut :**
```
Email: admin@smartnutrition.tn
Mot de passe: Admin@2526!
```

---

## 🤖 Intégrations IA & Services Externes

### 1. Anthropic Claude (IA Générative)

Utilisé via l'endpoint `view/backend/api/anthropic-messages.php` pour :

| Fonctionnalité | Endpoint | Description |
|---------------|----------|-------------|
| Génération de défis | `ai-challenge-generator.php` | Crée des défis personnalisés avec objectifs |
| Coach IA Chat | `ai-coach-chat.php` | Assistant nutrition/sport conversationnel |
| Coach progrès | `ai-progress-coach.php` | Analyse les performances et suggère des améliorations |
| Résumé défi | `ai-challenge-summary.php` | Génère un résumé attractif du défi |
| Image défi | `ai-challenge-image.php` | Génère une image illustrant le défi |

### 2. Modèles ML Python (`api_composer/`)

| Script | Rôle |
|--------|------|
| `main.py` | Serveur FastAPI — routes ML exposées |
| `medical_client.py` | Consultation API médicale externe |
| `retrain_model.py` | Réentraînement automatique des modèles |
| `sentiment_loader.py` | Analyse sentiment des messages/commentaires |
| `email_sender.py` | Envoi emails automatiques Python |

Endpoints ML exposés :
```
POST /predict-risk        → Prédiction de risque santé utilisateur
POST /challenge-analytics → Analytics et prédictions défis
POST /stats-predictions   → Statistiques utilisateurs + ML
```

### 3. Services Externes Intégrés

| Service | Usage | Fichier |
|---------|-------|---------|
| **Google OAuth 2.0** | Connexion sociale | `auth/google-auth.php` |
| **Twitter OAuth** | Connexion sociale | `auth/twitter-auth.php` |
| **PHPMailer / SMTP** | Emails transactionnels | `EmailService.php` |
| **Twilio** | SMS, notifications push | `config_services.php` |
| **Flouci** | Paiement défis (TND) | `challenge-payment.php` |
| **Stripe** | Paiement défis (international) | `challenge-payment.php` |
| **OpenWeatherMap** | Météo pour planning sport | `meteo_sport.php` |
| **Endroid QR Code** | QR codes participations | `QrCodeService.php` |
| **Reconnaissance faciale** | Login biométrique | `face_login.php` + Python |
| **TCM Herbs DB** | Plantes médicinales | `tcm_herbs.json` |

---

## 👥 Gestion des Utilisateurs

### Modèle `utilisateurs`

Champs principaux : `id_utilisateur`, `nom`, `prenom`, `email`, `password` (bcrypt), `role` (admin/user), `statut` (actif/suspendu/inactif), `photo_profil`, `avatar_url`, `date_naissance`, `genre`, `preference_langue`.

### Fonctionnalités

**Authentification**
- Inscription avec validation email
- Connexion standard (email + mot de passe)
- **Login facial** (Python face recognition + OpenCV)
- OAuth Google & Twitter
- Reset mot de passe par email (token + expiration)
- Réactivation de compte suspendu

**Profil & Paramètres**
- Mise à jour profil (nom, photo, préférences)
- Upload photo de profil
- Génération d'avatar IA (5 styles : anime, cartoon, minimal, pixel, avatar)
- Changement mot de passe sécurisé
- Suppression de compte

**Réseau Social**
- Envoi/réception/acceptation de demandes d'amis
- Liste d'amis avec statut en ligne
- Messagerie directe entre amis

**Administration**
- Listage/tri/filtrage utilisateurs
- Suspension/réactivation comptes
- Changement de rôle
- Statistiques utilisateurs avec prédictions ML

---

## 🥗 Gestion Alimentaire & Repas

### Module Aliments

**Modèle `aliments`** : catalogue de référence nutritionnelle complet.

Données par aliment : `nom`, `type` (légume/fruit/céréale/protéine/etc.), `categorie` (frais/sec/transformé), `calories`, `proteines`, `glucides`, `lipides`, `fibres`, `sucre`, `sodium`, `vitamines`, `co2`, `label_ecologique`, `prix`, `origine`, `allergenes`.

**Opérations disponibles :**
- Ajout / Modification / Suppression d'aliments (admin)
- Consultation avec filtres nutritionnels
- Calcul automatique des valeurs nutritionnelles
- Indicateur écologique (CO₂ par aliment)
- Détection allergènes

### Module Repas

**Modèle `repas`** + **`repas_aliments`** (composition).

- Création de repas personnalisés avec sélection d'aliments
- Calcul automatique des macros totaux (calories, protéines, glucides, lipides)
- Historique des repas par utilisateur
- Vue liste et détail côté frontend
- Recommandations IA basées sur l'historique nutritionnel

**API Recommandation IA :**
```
GET /api/recommandation_ia.php?user_id={id}
→ Analyse l'historique et recommande des repas adaptés
```

---

## 🏥 Dossier Médical & Régimes

### Dossier Médical

**Modèle `dossier_medical`** : données de santé individuelles.

Champs : `id_dossier`, `id_utilisateur`, `id_regime` (FK → regimes), `poids`, `taille`, `imc`, `groupe_sanguin`, `antecedents`, `allergies`, `maladies_chroniques`, `medicaments`, `date_creation`.

**Relation DossierMédical ↔ Régime :** One-to-Many. Chaque dossier possède un régime principal référencé par clé étrangère nullable (`ON DELETE SET NULL`).

**Opérations :**
- Création et gestion du dossier médical personnel
- Calcul IMC automatique
- Association à un régime alimentaire
- Export dossiers au format PDF/CSV
- Recherche de régimes par dossier

### Régimes Alimentaires

**Modèle `regimes`** : plans alimentaires prescrits.

Champs : `id_regime`, `nom_regime`, `type_regime`, `description`, `duree_jours`, `calories_quotidiennes`, `restrictions`, `objectif`.

**Types de régimes :** méditerranéen, cétogène, végétarien, végétalien, sans gluten, sans lactose, hyperprotéiné, hypocalorique, etc.

**Endpoints spéciaux :**
```
POST /api → action: attachRegime       (lier régime à dossier)
GET  /api → action: getAvailableRegimes (liste pour dropdown)
GET  /api → action: getDossierWithRegime (dossier + régime via JOIN)
```

---

## 📅 Planning & Sport/Sommeil

### Planning Sportif

**Modèle `planning`** : séances planifiées.

Champs : `id_planning`, `id_demandeplanning`, `titre`, `description`, `type_activite`, `date_debut`, `date_fin`, `duree_minutes`, `calories_estimees`, `niveau_difficulte`, `statut`.

**Demandes de Planning** (`demandeplanning`) :
- Formulaire de demande utilisateur (objectifs, disponibilités)
- Traitement admin + génération IA
- Approbation / Refus avec notification email

**Fonctionnalités avancées :**
- 🤖 **Génération automatique IA** (`planning/ai_generate.php`) : création d'un planning complet via Claude API
- 🌐 **Traduction multilingue** (`planning/ai_translate.php`) : traduction du planning en plusieurs langues
- 🌤️ **Planning météo** (`planning/meteo_sport.php`) : intégration météo pour adapter les activités outdoor
- 🍽️ **Recommandation restaurants** (`planning/restaurant_ia.php`) : suggestions IA de restaurants adaptés au régime
- 🧘 **Coach IA** (`planning/coach_ia.php`) : coaching personnalisé conversationnel

### Sport & Sommeil (`sportsommeil`)

Journal quotidien : `type_activite`, `duree_minutes`, `calories_brulees`, `distance_km`, `frequence_cardiaque`, `heures_sommeil`, `qualite_sommeil`, `notes`, `date`.

---

## 🏆 Défis & Challenges

### Système de Défis Complet

**Modèle `challenge`** : défis individuels ou collectifs.

Champs : `titre`, `description`, `type` (collectif/individuel/fitness/nutrition/bien-etre/sport/mental), `objectif`, `valeur_cible`, `date_debut`, `date_fin`, `statut` (en_attente/actif/termine/accepte/refuse), `streak_icon`, `image`, `nb_vues`, `nb_likes`, `est_payant`, `prix`, `devise` (TND), `mode_paiement`.

### Fonctionnalités Gamification

| Fonctionnalité | Description |
|----------------|-------------|
| 🎯 Objectifs chiffrés | Valeur cible numérique (pas, %, calories, etc.) |
| 🔥 Streaks | Icône emoji personnalisée par défi |
| ❤️ Likes | Système likes avec trigger SQL synchronisé |
| 👁️ Vues | Compteur de vues par défi |
| 📊 Analytics | Statistiques avec prédictions ML |
| 🎬 Lives | Streams vidéo en direct (`challenge_live_streams`) |
| 🎥 Vidéos | Vidéos liées aux défis (`challenge_videos`) |
| 🤖 IA Summary | Résumé attractif généré par Claude AI |
| 🖼️ IA Image | Image illustrative générée par IA |

### Paiement Défis (`paiement_defi`)

- Intégration **Flouci** (paiement TND en ligne)
- Intégration **Stripe** (paiement international)
- Gestion des transactions, statuts, remboursements
- Tableau de bord paiements admin

### Participants (`participant`)

Champs : `id_participant`, `id_challenge`, `id_utilisateur`, `progression`, `statut_participation`, `date_inscription`, `date_completion`.

- Inscription / Désinscription aux défis
- Suivi de progression individuelle
- Classement participants
- Notifications challenger

---

## 🎪 Événements & Participations

### Événements (`evenement`)

Gestion complète d'événements : `titre`, `description`, `lieu`, `date_debut`, `date_fin`, `capacite_max`, `type_evenement`, `statut`, `image`, `prix`.

**Backend Admin :**
- CRUD complet (add/update/delete/show/list)
- Gestion des places disponibles
- Statistiques participations

### Participations (`participation`)

- Inscription en ligne aux événements
- **Génération QR Code** unique par participation
- **Carte de participation** PDF/image
- Scan QR Code à l'entrée
- Gestion liste d'attente

```php
// Génération QR Code via QrCodeService
$qrCode = QrCodeService::generate($participation_id, $user_id);
// Rendu PNG ou SVG selon config
```

---

## 💬 Chat Temps Réel

### Architecture WebSocket

Le serveur Ratchet (`view/backend/ws/server.php`) gère les connexions WebSocket persistantes.

```
Client Browser ←──WebSocket (ws://localhost:8080)──→ ChatServer.php
                                                       ├── chat_threads (BDD)
                                                       ├── chat_messages (BDD)
                                                       ├── chat_attachments
                                                       └── chat_notifications
```

### Fonctionnalités Chat

| Feature | Description |
|---------|-------------|
| 💬 Messages texte | Envoi/réception temps réel |
| 📎 Pièces jointes | Upload images, fichiers |
| 🎥 Vidéos | Partage vidéos WebM/MP4 |
| 🔔 Notifications | Push notifications non-lues |
| 📢 Channels | Canaux thématiques (`chat_threads`) |
| 🔴 Live streams | Streams live dans les défis |
| 🤖 Chatbot IA | Assistant IA intégré au chat |

### API Chat REST

```
GET  /api/chat/messages.php?thread_id={id}   → Historique messages
POST /api/chat/message.php                   → Envoyer message
GET  /api/chat/channels.php                  → Liste des canaux
POST /api/chat/attachments.php               → Upload fichier
GET  /api/chat/notifications.php             → Notifications
GET  /api/chat/live-streams.php              → Streams actifs
```

---

## 🛡️ Sécurité & Authentification

### Mesures de Sécurité

| Mesure | Implémentation |
|--------|---------------|
| Mots de passe | `password_hash()` PHP (bcrypt) |
| Sessions | `session_start()` + `session_regenerate_id()` |
| Injection SQL | PDO prepared statements partout |
| XSS | `htmlspecialchars()` sur toutes les sorties |
| CSRF | Tokens de session vérifiés |
| Upload fichiers | Validation MIME type + extension whitelist |
| Auth token reset | Token aléatoire + expiration 1h |
| Reconnaissance faciale | Vérification biométrique Python |

### Rôles & Permissions

| Rôle | Accès |
|------|-------|
| `admin` | Tableau de bord admin complet, CRUD tous modules |
| `user` | Dashboard personnel, modules frontend |
| Invité | Pages publiques, inscription |

### OAuth Social

- **Google OAuth 2.0** : Flux standard, scope email+profile
- **Twitter OAuth 1.0a** : Authentification Twitter
- Liaison compte existant si email identique

---

## 🎨 Interface Frontend / Backend

### Frontend (SPA modulaire)

Architecture Single Page Application avec chargement dynamique des modules via `module-loader.js`.

**Pages principales :**

| Page | Fichier | Description |
|------|---------|-------------|
| Accueil | `index.html` | Landing page publique |
| Connexion | `login.html` | Auth + OAuth |
| Tableau de bord | `dashboard.html` | Hub utilisateur |
| Amis | `friends.html` | Réseau social |
| Santé | `modules/health.html` | Dossier médical + régimes |
| Planning | `modules/planning.html` | Planning sportif |
| Défis | `modules/challenges.html` | Défis & gamification |
| Événements | `modules/events.html` | Agenda & inscriptions |
| Repas | `modules/meals.html` | Journal alimentaire |
| Profil | `users/profile.html` | Paramètres compte |

**Fichiers JS :**
- `dashboard.js` → Logique dashboard
- `challenges.js` / `challenges-complete.js` → Gestion défis
- `health.controller.js` → Contrôleur santé
- `chat-realtime.js` → Client WebSocket
- `chat-notify.js` → Notifications temps réel
- `friends.js` → Réseau social
- `login.js` / `forgot.js` → Authentification

### Backend Admin (Multi-modules)

Dashboard admin SPA avec `admin-module-loader.js`.

**Modules Admin :**

| Module | Fichier HTML | Fonctionnalités |
|--------|-------------|-----------------|
| Dashboard | `modules/dashboard-admin.html` | KPIs, graphiques, stats ML |
| Utilisateurs | `modules/users-admin.html` | CRUD + suspension + ML |
| Défis | `modules/challenges-admin.html` | CRUD + analytics IA |
| Planning | `modules/planning-admin.html` | Gestion demandes + IA |
| Santé | `modules/health-admin.html` | Dossiers + régimes |
| Repas | `modules/meals-admin.html` | Aliments + repas |
| Événements | `modules/events-admin.html` | CRUD + participations |
| Activités | `modules/activity-admin.html` | Sport & sommeil |

---

## 📊 Plan d'Animation Complet

Ce plan décrit les **interactions dynamiques**, **transitions**, et **effets visuels** qui animent l'application SmartNutrition. Il couvre l'ensemble des modules avec des indications d'implémentation précises.

---

### 🏠 1. Page d'Accueil (`index.html`)

#### Hero Section
```css
/* Animation d'entrée hero */
.hero-title {
  animation: fadeInUp 0.8s ease-out;
}
.hero-subtitle {
  animation: fadeInUp 0.8s ease-out 0.2s both;
}
.hero-cta {
  animation: fadeInUp 0.8s ease-out 0.4s both;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to   { opacity: 1; transform: translateY(0); }
}
```

#### Chiffres clés (Counter Animation)
```javascript
// Compteur animé : 0 → valeur cible
function animateCounter(el, target, duration = 2000) {
  let start = 0;
  const step = target / (duration / 16);
  const timer = setInterval(() => {
    start += step;
    if (start >= target) { el.textContent = target; clearInterval(timer); }
    else el.textContent = Math.floor(start);
  }, 16);
}
// Déclenchement : IntersectionObserver sur .stats-section
```

#### Cards Features (Stagger Reveal)
```javascript
// Apparition en cascade au scroll
const observer = new IntersectionObserver(entries => {
  entries.forEach((entry, i) => {
    if (entry.isIntersecting) {
      setTimeout(() => entry.target.classList.add('visible'), i * 150);
    }
  });
}, { threshold: 0.1 });
document.querySelectorAll('.feature-card').forEach(c => observer.observe(c));
```

---

### 🔐 2. Authentification (`login.html`, `register.html`)

#### Formulaire Login
- **Shake animation** sur erreur de mot de passe :
```css
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-8px); }
  40%, 80% { transform: translateX(8px); }
}
.form-error { animation: shake 0.4s ease; }
```
- **Loading spinner** pendant la requête Ajax
- **Transition smooth** vers dashboard après succès (`opacity 0 → 1`)
- **Social login buttons** : effet hover avec translation Y -2px + shadow

#### Connexion Faciale
```javascript
// Flux webcam → détection visage → comparaison Python API
navigator.mediaDevices.getUserMedia({ video: true })
  .then(stream => {
    videoEl.srcObject = stream;
    // Capture frame → POST /view/backend/face_login.php
    // Affichage : cercle pulsant autour du visage détecté
  });
```

---

### 📊 3. Dashboard (`dashboard.html`)

#### Widget Cards (Entrée)
```css
/* Cards entrent depuis la gauche avec délai croissant */
.dashboard-card:nth-child(1) { animation: slideInLeft 0.5s ease 0.1s both; }
.dashboard-card:nth-child(2) { animation: slideInLeft 0.5s ease 0.2s both; }
.dashboard-card:nth-child(3) { animation: slideInLeft 0.5s ease 0.3s both; }
.dashboard-card:nth-child(4) { animation: slideInLeft 0.5s ease 0.4s both; }
```

#### Graphiques Nutritionnels
```javascript
// Chart.js — macros journaliers (donut animé)
new Chart(ctx, {
  type: 'doughnut',
  data: { datasets: [{ data: [proteins, carbs, fats], /* ... */ }] },
  options: {
    animation: { animateRotate: true, duration: 1200, easing: 'easeInOutQuart' }
  }
});
```

#### Progress Bars Défis
```css
.progress-bar-fill {
  width: 0%;
  transition: width 1.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
/* Au rendu : .progress-bar-fill.animate { width: var(--target-width); } */
```

#### Notifications Toast
```javascript
function showToast(message, type = 'success') {
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = message;
  document.body.appendChild(toast);
  // Slide in depuis la droite
  toast.animate([
    { transform: 'translateX(100%)', opacity: 0 },
    { transform: 'translateX(0)', opacity: 1 }
  ], { duration: 300, fill: 'forwards' });
  // Auto-dismiss après 4s
  setTimeout(() => toast.animate([
    { transform: 'translateX(0)', opacity: 1 },
    { transform: 'translateX(100%)', opacity: 0 }
  ], { duration: 300, fill: 'forwards' }).onfinish = () => toast.remove(), 4000);
}
```

---

### 🏆 4. Module Défis (`challenges.html`)

#### Cards Défis (Hover 3D)
```css
.challenge-card {
  transform-style: preserve-3d;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.challenge-card:hover {
  transform: perspective(1000px) rotateX(-3deg) rotateY(3deg) translateY(-8px);
  box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}
```

#### Streak Badge (Pulse)
```css
.streak-badge {
  animation: pulse 2s infinite;
}
@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50%       { transform: scale(1.15); box-shadow: 0 0 0 8px rgba(255,100,0,0.2); }
}
```

#### Like Button (Micro-animation)
```javascript
likeBtn.addEventListener('click', function() {
  this.classList.add('liked');
  // Particules confetti locales
  createParticles(this.getBoundingClientRect());
  // Incrémente compteur avec animation bounce
  counterEl.animate([
    { transform: 'scale(1)' },
    { transform: 'scale(1.4)' },
    { transform: 'scale(1)' }
  ], { duration: 400, easing: 'cubic-bezier(0.175, 0.885, 0.32, 1.275)' });
});
```

#### Génération IA (Loading State)
```javascript
// Affichage skeleton + spinner pendant appel Claude API
async function generateChallenge() {
  showSkeleton();
  showSpinner('Génération en cours avec Claude AI...');
  const res = await fetch('/view/backend/api/ai-challenge-generator.php', {
    method: 'POST', body: JSON.stringify({ theme, duration, difficulty })
  });
  const data = await res.json();
  hideSkeleton();
  // Typed.js effect pour afficher le texte généré progressivement
  new Typed('#challenge-desc', { strings: [data.description], typeSpeed: 20 });
}
```

#### Paiement Modal (Transition)
```javascript
// Modal paiement avec backdrop blur
payBtn.addEventListener('click', () => {
  paymentModal.style.display = 'flex';
  paymentModal.animate([
    { opacity: 0, transform: 'scale(0.9)' },
    { opacity: 1, transform: 'scale(1)' }
  ], { duration: 250, fill: 'forwards' });
});
```

---

### 📅 5. Module Planning

#### Génération IA Planning (Streaming)
```javascript
// Affichage progressif via Server-Sent Events ou polling
const eventSource = new EventSource('/view/backend/planning/ai_generate.php?stream=1');
eventSource.onmessage = (e) => {
  planningContainer.innerHTML += e.data; // Texte s'affiche mot par mot
};
```

#### Calendrier (Drag & Drop)
```javascript
// FullCalendar.js intégration
calendar.addEvent({
  id: planning.id,
  title: planning.titre,
  start: planning.date_debut,
  end: planning.date_fin,
  color: getActivityColor(planning.type_activite)
});
// Drag d'événements → PATCH API pour MAJ dates
calendar.on('eventDrop', ({ event }) => updatePlanningDate(event.id, event.start));
```

#### Widget Météo (Météo Sport)
```css
/* Icône météo animée */
.weather-icon.sun    { animation: spin 8s linear infinite; }
.weather-icon.rain   { animation: raindrop 1s linear infinite; }
.weather-icon.cloud  { animation: float 3s ease-in-out infinite; }

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50%       { transform: translateY(-5px); }
}
```

---

### 🥗 6. Module Alimentation / Repas

#### Sélecteur d'Aliments (Autocomplete)
```javascript
// Recherche live avec debounce 300ms
const searchInput = document.getElementById('aliment-search');
searchInput.addEventListener('input', debounce(async (e) => {
  const results = await fetch(`/api?action=search&q=${e.target.value}`).then(r => r.json());
  renderDropdown(results);
}, 300));
```

#### Ajout Aliment au Repas (Animation)
```javascript
// Carte aliment "vole" vers le panier repas
function addToMeal(alimentCard) {
  const clone = alimentCard.cloneNode(true);
  clone.style.position = 'fixed';
  clone.style.zIndex = '9999';
  // Animation Bezier de la carte vers le conteneur repas
  const basketPos = mealBasket.getBoundingClientRect();
  clone.animate([
    { top: alimentCard.getBoundingClientRect().top + 'px', transform: 'scale(1)', opacity: 1 },
    { top: basketPos.top + 'px', transform: 'scale(0.1)', opacity: 0 }
  ], { duration: 600, easing: 'cubic-bezier(0.55, 0, 1, 0.45)' })
  .onfinish = () => { clone.remove(); updateMealTotals(); };
  document.body.appendChild(clone);
}
```

#### Gauge Macros (CircularProgress)
```javascript
// Rendu gauge SVG animé pour calories/macros
function renderGauge(svgEl, value, max, color) {
  const percent = Math.min(value / max, 1);
  const dashArray = 2 * Math.PI * 45; // rayon 45
  svgEl.querySelector('circle.fill').style.strokeDashoffset =
    dashArray * (1 - percent);
  svgEl.querySelector('circle.fill').style.stroke = color;
}
```

---

### 🏥 7. Module Santé

#### IMC Indicator (Animated)
```javascript
// Aiguille de jauge IMC animée
function animateIMC(imc) {
  const angle = ((imc - 15) / (40 - 15)) * 180 - 90; // -90° à +90°
  needle.style.transform = `rotate(${angle}deg)`;
  needle.style.transition = 'transform 1.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
}
```

#### Export PDF (Progress)
```javascript
// Indicateur de progression export
async function exportDossier(id) {
  showProgressBar(0);
  const response = await fetch(`/view/backend/health/exportDossiers.php?id=${id}`);
  const reader = response.body.getReader();
  while (true) {
    const { done, value } = await reader.read();
    if (done) break;
    updateProgressBar(/* progress */);
  }
  hideProgressBar();
  downloadFile(response);
}
```

---

### 💬 8. Chat Temps Réel

#### Messages (Bulles Animées)
```css
/* Apparition bulle de message */
.message-bubble {
  animation: bubbleIn 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes bubbleIn {
  from { transform: scale(0) translateY(10px); opacity: 0; }
  to   { transform: scale(1) translateY(0); opacity: 1; }
}

/* Indicateur "en train d'écrire" */
.typing-indicator span {
  animation: bounce 1.2s infinite;
}
.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
```

#### Connexion WebSocket
```javascript
// Feedback visuel connexion WS
const ws = new WebSocket('ws://localhost:8080');
ws.onopen = () => {
  statusDot.classList.add('connected'); // Dot vert pulsant
  showToast('Connecté au chat en temps réel', 'success');
};
ws.onclose = () => {
  statusDot.classList.remove('connected');
  // Reconnexion automatique avec backoff exponentiel
  setTimeout(() => reconnectWS(), Math.pow(2, retries++) * 1000);
};
ws.onmessage = ({ data }) => {
  const msg = JSON.parse(data);
  appendMessage(msg);
  playNotificationSound();
};
```

#### Upload Fichier (Drag & Drop)
```javascript
chatArea.addEventListener('dragover', e => {
  e.preventDefault();
  chatArea.classList.add('drag-over'); // Overlay bleu + icône upload
});
chatArea.addEventListener('drop', async e => {
  e.preventDefault();
  chatArea.classList.remove('drag-over');
  const files = e.dataTransfer.files;
  for (const file of files) await uploadAttachment(file);
});
```

---

### 👥 9. Module Amis

#### Réseau Social (Animations)
```javascript
// Requête d'ami : animation cœur/check
sendFriendRequest(userId).then(() => {
  btn.innerHTML = '✓ Demande envoyée';
  btn.animate([
    { transform: 'scale(1.2)' },
    { transform: 'scale(1)' }
  ], { duration: 300 });
});
```

#### Statut En Ligne (Pulse Dot)
```css
.online-dot {
  width: 10px; height: 10px;
  background: #22c55e;
  border-radius: 50%;
  animation: onlinePulse 2s infinite;
}
@keyframes onlinePulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5); }
  50%       { box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
}
```

---

### 🖥️ 10. Admin Dashboard

#### Entrée des Graphiques (Chart.js)
```javascript
// Graphique barres — utilisateurs par mois
new Chart(ctx, {
  type: 'bar',
  options: {
    animation: {
      onProgress: ({ chart }) => {
        // Affichage chiffres qui montent en temps réel
        chart.data.datasets[0].data.forEach((v, i) => {
          const progress = chart.getDatasetMeta(0).data[i].getProps(['y']);
          // Rendu valeur animée
        });
      },
      duration: 1500, easing: 'easeInOutBounce'
    }
  }
});
```

#### Prédictions ML (Skeleton Loading)
```javascript
// Skeleton screens pendant chargement données ML
async function loadMLPredictions() {
  showSkeletons(5); // 5 skeleton cards
  const data = await fetch('/view/backend/api/ml-predict-risk.php').then(r => r.json());
  hideSkeletons();
  renderPredictions(data); // Cards avec badge couleur risque
}
```

#### Drag & Drop Ordre Défis
```javascript
// Réorganisation drag défis (admin)
Sortable.create(challengeList, {
  animation: 150,
  ghostClass: 'sortable-ghost',
  onEnd: ({ oldIndex, newIndex }) => {
    updateChallengeOrder(challengeIds[oldIndex], newIndex);
  }
});
```

---

### 🎯 Résumé Plan Animation — Matrice Priorités

| Animation | Module | Priorité | Librairie |
|-----------|--------|----------|-----------|
| Hero FadeIn | Accueil | ⭐⭐⭐⭐⭐ | CSS |
| Counter animé | Accueil | ⭐⭐⭐⭐ | JS Vanilla |
| Shake erreur | Login | ⭐⭐⭐⭐⭐ | CSS |
| Webcam face login | Login | ⭐⭐⭐⭐ | WebRTC + Python |
| Progress bars | Dashboard | ⭐⭐⭐⭐⭐ | CSS |
| Charts animés | Dashboard/Admin | ⭐⭐⭐⭐⭐ | Chart.js |
| 3D hover cards | Défis | ⭐⭐⭐⭐ | CSS 3D |
| Pulse streak | Défis | ⭐⭐⭐⭐ | CSS |
| Like particules | Défis | ⭐⭐⭐ | JS Canvas |
| Typed.js IA | Défis/Planning | ⭐⭐⭐⭐ | Typed.js |
| Bulles chat | Chat | ⭐⭐⭐⭐⭐ | CSS |
| Typing indicator | Chat | ⭐⭐⭐⭐⭐ | CSS |
| WebSocket status | Chat | ⭐⭐⭐⭐ | CSS + JS |
| Gauge IMC | Santé | ⭐⭐⭐⭐ | SVG + JS |
| Fly-to-basket | Repas | ⭐⭐⭐ | Web Animations API |
| Skeleton loading | Admin/IA | ⭐⭐⭐⭐⭐ | CSS |
| Drag & drop | Admin/Défis | ⭐⭐⭐⭐ | SortableJS |
| Toast notifications | Global | ⭐⭐⭐⭐⭐ | JS Vanilla |
| Stagger scroll | Global | ⭐⭐⭐⭐ | IntersectionObserver |

---

## 🧪 Tests & Qualité

### Fichiers de Test Disponibles

| Fichier | Description |
|---------|-------------|
| `test-session.php` | Vérification sessions PHP |
| `view/backend/test.html` | Tests composants UI |
| `view/backend/test.php` | Tests API backend |
| `view/backend/debug-events.html` | Débogage événements |
| `view/backend/test-events-simple.html` | Tests événements simplifiés |
| `view/backend/api/ai-env-check.php` | Vérification variables env IA |
| `view/backend/test_face_backend.php` | Tests reconnaissance faciale |

### Recommandations Qualité

```bash
# Lint PHP
php -l controller/*.php

# Vérification dépendances
composer outdated

# Audit sécurité Python
pip audit

# Tests WebSocket
websocat ws://localhost:8080
```

---

## 🤝 Contribution

### Workflow de Développement

```bash
# 1. Fork & clone
git clone https://github.com/VOTRE-USERNAME/Esprit-PW-2A19-2526-SmartNutrition.git

# 2. Créer une branche feature
git checkout -b feature/nom-de-la-feature

# 3. Développer et committer
git add .
git commit -m "feat(module): description courte"

# 4. Push et Pull Request
git push origin feature/nom-de-la-feature
```

### Convention de Commits

```
feat(module):     Nouvelle fonctionnalité
fix(module):      Correction de bug
refactor(module): Refactoring sans changement fonctionnel
style(module):    CSS/UI uniquement
docs(module):     Documentation
test(module):     Tests
chore:            Maintenance, deps
```

### Structure Branches

| Branche | Rôle |
|---------|------|
| `main` | Production stable |
| `develop` | Intégration développement |
| `feature/*` | Nouvelles fonctionnalités |
| `fix/*` | Corrections de bugs |
| `release/*` | Préparation release |

---

## 👨‍💻 Équipe

**Promotion 2A19 — ESPRIT School of Engineering**
**Année académique 2025–2026**

| Membre | Modules principaux | GitHub |
|--------|-------------------|--------|
| **Kosay Labidi** | Lead Dev, Défis, WebSocket, IA | [@kosay-labidi](https://github.com/kosay-labidi) |
| *Membre 2* | Utilisateurs, Auth, Profil | — |
| *Membre 3* | Alimentation, Repas, Nutrition | — |
| *Membre 4* | Planning, Sport/Sommeil, Météo | — |
| *Membre 5* | Santé, Dossier médical, Régimes | — |
| *Membre 6* | Événements, Participations, QR | — |

> **Encadrant :** Équipe pédagogique ESPRIT — Module Projet Web

---

## 📄 Licence

Ce projet est développé dans le cadre académique de l'**ESPRIT School of Engineering**.

```
MIT License — SmartNutrition GaiaLumen
Copyright (c) 2025-2026 — Promo 2A19 ESPRIT
```

---

<div align="center">

**🌿 SmartNutrition — Mangez mieux, vivez mieux, avec l'IA**

*Développé avec ❤️ à ESPRIT School of Engineering*

[![ESPRIT](https://img.shields.io/badge/ESPRIT-School_of_Engineering-1d4ed8?style=flat-square)](https://esprit.tn)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat-square&logo=php)](https://php.net)
[![AI Powered](https://img.shields.io/badge/AI-Powered-orange?style=flat-square)](https://anthropic.com)

</div>
