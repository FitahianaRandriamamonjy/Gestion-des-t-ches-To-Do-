# TaskFlow — Gestionnaire de tâches (Symfony)

TaskFlow est une application web de gestion de tâches en équipe, développée avec **Symfony** et **Doctrine ORM**. Elle permet de créer, filtrer, assigner et suivre des tâches, avec un tableau de bord, un calendrier, des statistiques d'équipe et un système de notifications, le tout dans une interface entièrement personnalisée.

# Todo List Application (Symfony)

> **Lien de démonstration en ligne :** [https://todo-app-fitahiana.onrender.com](https://todo-app-fitahiana.onrender.com)

---

## Aperçu des fonctionnalités

- **Authentification** — inscription, connexion, déconnexion, mots de passe hashés
- **Tâches** — création, modification, suppression, changement de statut en un clic, priorité (Basse/Moyenne/Haute), échéance, description
- **Assignation** — affecter une tâche à un membre de l'équipe (ou la désaffecter) depuis la liste
- **Filtres & recherche** — par statut, priorité, utilisateur assigné, recherche texte, avec pagination
- **Tableau de bord** — compteurs de tâches, graphique d'activité (semaine/mois), répartition des statuts, prochaines échéances, score de productivité
- **Calendrier mensuel** — visualisation des tâches par date d'échéance
- **Statistiques d'équipe** — tendance sur 8 semaines, répartition par priorité/statut, classement des membres
- **Équipe** — vue d'ensemble de la charge et de la productivité de chaque membre
- **Notifications** — générées automatiquement à partir de l'état des tâches (retard, échéance proche, nouvelle affectation), filtrées par utilisateur (les administrateurs voient tout, les autres uniquement ce qui les concerne)
- **Profil & paramètres** — modification des informations personnelles et du mot de passe
- **Permissions** — seul le créateur d'une tâche (ou un administrateur) peut la supprimer

## Stack technique

| Composant | Technologie |
|---|---|
| Framework | Symfony 7.4 |
| ORM | Doctrine ORM 3 |
| Base de données | MySQL 8 |
| Templates | Twig |
| Frontend | CSS custom (design system "TaskFlow"), Chart.js, Bootstrap Icons |
| Auth | Symfony Security (form login) |

## Prérequis

- PHP >= 8.2 avec les extensions `ctype`, `iconv`
- Composer
- MySQL 8 (ou Docker, voir plus bas)
- [Symfony CLI](https://symfony.com/download) (optionnel, mais recommandé)

## Installation

### 1. Cloner le projet

```bash
git clone https://github.com/<votre-utilisateur>/<votre-repo>.git
cd <votre-repo>
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer l'environnement

Copiez le fichier d'exemple puis adaptez-le si besoin :

```bash
cp .env.example .env.local
```

Éditez `.env.local` (fichier ignoré par Git) et générez un vrai secret :

```bash
php -r "echo bin2hex(random_bytes(16)), PHP_EOL;"
```

Collez la valeur obtenue dans `APP_SECRET` de `.env.local`.

### 4. Démarrer la base de données

**Option A — avec Docker (recommandé) :**

```bash
docker compose up -d
```

Cela lance un conteneur MySQL correspondant au `DATABASE_URL` par défaut.

**Option B — avec une instance MySQL locale :**

Adaptez `DATABASE_URL` dans `.env.local` avec vos identifiants.

### 5. Créer le schéma de la base

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 6. Charger les données de démonstration (recommandé)

```bash
php bin/console doctrine:fixtures:load
```

Cela crée 4 comptes de démonstration et une vingtaine de tâches réparties sur plusieurs semaines (pour que les graphiques du tableau de bord aient tout de suite du contenu) :

| Email | Mot de passe | Rôle |
|---|---|---|
| admin@todo.local | password123 | Administrateur |
| alice@todo.local | password123 | Membre |
| bob@todo.local | password123 | Membre |
| chloe@todo.local | password123 | Membre |

### 7. Lancer le serveur

```bash
symfony serve
# ou
php -S 127.0.0.1:8000 -t public/
```

Ouvrez `http://127.0.0.1:8000/connexion` et connectez-vous avec l'un des comptes ci-dessus (ou créez le vôtre via `/inscription`).

## Routes principales

| Route | Méthode | Description |
|---|---|---|
| `/` | GET | Redirige vers le tableau de bord |
| `/dashboard` | GET | Tableau de bord |
| `/taches` | GET | Liste des tâches, filtres, pagination |
| `/taches/nouvelle` | GET/POST | Créer une tâche |
| `/taches/{id}` | GET | Détail d'une tâche |
| `/taches/{id}/modifier` | GET/POST | Modifier une tâche |
| `/taches/{id}/statut/{status}` | POST | Changer le statut |
| `/taches/{id}/affecter` | POST | Affecter/désaffecter un utilisateur |
| `/taches/{id}/supprimer` | POST | Supprimer une tâche (créateur ou admin) |
| `/equipe` | GET | Vue d'ensemble de l'équipe |
| `/calendrier` | GET | Calendrier mensuel |
| `/statistiques` | GET | Statistiques et classement |
| `/notifications` | GET | Liste des notifications |
| `/profil` | GET | Profil de l'utilisateur connecté |
| `/parametres` | GET/POST | Modifier profil et mot de passe |
| `/connexion` | GET/POST | Connexion |
| `/inscription` | GET/POST | Inscription |
| `/deconnexion` | GET | Déconnexion |

## Structure du projet

```
src/
├── Controller/       # Un contrôleur par fonctionnalité (Task, Dashboard, Team, Stats, Calendar, ...)
├── Entity/           # Task, User
├── Enum/             # TaskStatus, TaskPriority
├── Form/             # Formulaires (Task, inscription, profil, mot de passe)
├── Repository/       # Requêtes Doctrine (filtres, pagination, comptages)
├── Service/          # NotificationService (notifications dérivées de l'état des tâches)
├── Twig/             # Extension Twig (avatars, temps relatif, notifications, productivité)
└── DataFixtures/      # Données de démonstration
templates/
├── base.html.twig    # Layout principal (sidebar, topbar, notifications)
├── auth_base.html.twig
├── dashboard/, task/, team/, stats/, calendar/, notification/, profile/, settings/, security/, registration/
public/
└── css/dashboard.css # Design system "TaskFlow"
```

## Déploiement

### Option 1 — Serveur classique (VPS) avec Nginx + PHP-FPM

1. Installez PHP 8.2+, l'extension `pdo_mysql`, Composer, et un serveur MySQL.
2. Clonez le projet sur le serveur puis :
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Créez un fichier `.env.local.php` optimisé pour la prod :
   ```bash
   composer dump-env prod
   ```
   ou définissez directement les variables d'environnement (`APP_ENV=prod`, `APP_SECRET`, `DATABASE_URL`) au niveau du système/service.
4. Migrez la base de données :
   ```bash
   php bin/console doctrine:migrations:migrate --no-interaction
   ```
5. Videz et réchauffez le cache :
   ```bash
   php bin/console cache:clear --env=prod
   ```
6. Configurez le **document root de votre serveur web sur le dossier `public/`** (jamais la racine du projet).
   Exemple de bloc Nginx minimal :
   ```nginx
   server {
       listen 80;
       server_name votre-domaine.com;
       root /var/www/todo-app/public;

       location / {
           try_files $uri /index.php$is_args$args;
       }

       location ~ ^/index\.php(/|$) {
           fastcgi_pass unix:/run/php/php8.2-fpm.sock;
           fastcgi_split_path_info ^(.+\.php)(/.*)$;
           include fastcgi_params;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
       }

       location ~ \.php$ {
           return 404;
       }
   }
   ```
7. Donnez les droits d'écriture au serveur web sur `var/`.

### Option 2 — Docker

Le projet inclut un `compose.yaml` pour la base de données. Pour containeriser l'application elle-même, ajoutez un `Dockerfile` basé sur une image `php:8.2-fpm` avec les extensions Doctrine nécessaires, ou utilisez le [setup Docker officiel de Symfony (FrankenPHP)](https://symfony.com/doc/current/setup/docker.html).

### Option 3 — Plateformes PaaS (Platform.sh, Fly.io, Railway, etc.)

Ces plateformes détectent automatiquement les projets Symfony (via `composer.json`) et gèrent le build, la base de données et les migrations. Définissez au minimum les variables d'environnement `APP_ENV=prod`, `APP_SECRET` et `DATABASE_URL` dans leur interface, puis déployez depuis votre dépôt GitHub.

Quelle que soit l'option choisie, pensez à :
- ne **jamais** committer de vrai `APP_SECRET` ou mot de passe de base de données (utilisez `.env.local` ou les variables d'environnement de la plateforme),
- exécuter les migrations à chaque déploiement,
- ne pas charger les fixtures de démonstration en production.

## Pistes d'évolution

- Notifications par email (Symfony Mailer) en plus du flux in-app
- Sous-tâches / listes de contrôle
- Pièces jointes sur une tâche
- API REST (API Platform) pour un client mobile
- Historique des changements de statut (audit log)
- Tests automatisés (PHPUnit)

.
