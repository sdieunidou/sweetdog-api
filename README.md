# SweetDog API

API REST développée avec Symfony 7.3, suivant une architecture hexagonale (Clean Architecture) pour une séparation claire des responsabilités et une maintenabilité optimale.

## 🚀 Technologies

- **Framework**: Symfony 7.3
- **PHP**: 8.2+
- **Base de données**: PostgreSQL 16
- **Authentification**: FusionAuth
- **Architecture**: Hexagonale (Domain, Application, Infrastructure)
- **Tests**: PHPUnit 12.4
- **Qualité de code**:
  - PHPStan (niveau 5)
  - PHPMD
  - Deptrac (architecture)
  - PHP-CS-Fixer

## 📋 Prérequis

- PHP 8.4 ou supérieur
- Composer
- Docker et Docker Compose
- Symfony CLI (optionnel, pour le serveur de développement)

## 🛠️ Installation

1. **Cloner le dépôt**
```bash
git clone <repository-url>
cd sweetdog-api
```

2. **Installer les dépendances**
```bash
make install
```

Cette commande va :
- Installer les dépendances Composer
- Installer les certificats SSL pour le serveur Symfony
- Construire les images Docker

3. **Démarrer les services**
```bash
make start
```

Cette commande démarre :
- PostgreSQL (port 5432)
- FusionAuth (port 9011)
- Le serveur Symfony local

4. **Charger les données initiales**
```bash
make load-data
```

Cette commande exécute les migrations et charge les fixtures.

## ⚙️ Configuration

### Variables d'environnement

Créez un fichier `.env.local` à la racine du projet pour personnaliser la configuration :

```env
# Base de données
POSTGRES_DB=app
POSTGRES_USER=app
POSTGRES_PASSWORD=!ChangeMe!

# FusionAuth
DATABASE_USER=fusionauth
DATABASE_PASSWORD=!ChangeMe!
```

### FusionAuth

FusionAuth est configuré automatiquement via le fichier `docker/fusionauth/kickstart.json`. Les identifiants par défaut sont :
- Email : `admin@admin.com`
- Mot de passe : `password`

## 📚 Utilisation

### Authentification

Les endpoints protégés nécessitent un header d'authentification :
```
Authorization: Bearer <token>
```

Le token est obtenu via l'endpoint `/api/auth/login`.

## 🧪 Tests

### Exécuter tous les tests
```bash
make tests
```

### Exécuter un test spécifique
```bash
make tests filter=NomDuTest
```

Les tests incluent :
- Tests fonctionnels pour les endpoints API
- Tests unitaires pour la logique métier
- Tests d'intégration

## 🔍 Qualité de code

### Vérifications locales

Exécutez toutes les vérifications de qualité de code :
```bash
make ci-check
```

Cette commande exécute :
- **PHP-CS-Fixer** : Vérification du style de code
- **PHPStan** : Analyse statique (niveau 5)
- **PHPMD** : Détection de code mort et problèmes potentiels
- **Deptrac** : Vérification de l'architecture hexagonale
- **Lint Symfony** : Validation du conteneur et des fichiers YAML

### Formater le code

Pour corriger automatiquement le style de code :
```bash
make lint
```

## 🏗️ Architecture

Le projet suit une architecture hexagonale (Clean Architecture) avec trois couches principales :

```
src/
├── Domain/          # Logique métier pure (entités, interfaces)
├── Application/     # Cas d'usage (use cases)
└── Infrastructure/  # Implémentations techniques (controllers, repositories)
```

### Règles d'architecture (Deptrac)

- **Domain** : Ne dépend de rien
- **Application** : Dépend uniquement de Domain
- **Infrastructure** : Dépend de Application et Domain

Cette architecture garantit :
- Indépendance de la logique métier vis-à-vis des frameworks
- Testabilité accrue
- Maintenabilité et évolutivité

## 🔄 CI/CD

Le projet utilise GitHub Actions pour l'intégration continue. Le workflow CI (`/.github/workflows/ci.yml`) exécute :

1. Installation des dépendances
2. Démarrage des services Docker (PostgreSQL, FusionAuth)
3. Audit de sécurité Composer
4. Vérifications de qualité de code (PHP-CS-Fixer, PHPStan, PHPMD, Deptrac)
5. Lint Symfony (conteneur, YAML)
6. Exécution des tests

## 📁 Structure du projet

```
sweetdog-api/
├── bin/                    # Scripts exécutables
├── config/                 # Configuration Symfony
├── docker/                 # Configuration Docker
│   └── fusionauth/        # Configuration FusionAuth
├── migrations/             # Migrations Doctrine
├── public/                 # Point d'entrée web
├── src/
│   ├── Application/        # Cas d'usage
│   │   ├── Auth/
│   │   └── Contact/
│   ├── Domain/             # Logique métier
│   │   ├── Auth/
│   │   └── Contact/
│   └── Infrastructure/     # Implémentations techniques
│       ├── Auth/
│       └── Contact/
├── tests/                  # Tests
│   ├── Auth/
│   ├── Contact/
│   └── Shared/
├── .github/
│   └── workflows/          # GitHub Actions
├── composer.json           # Dépendances PHP
├── compose.yaml            # Docker Compose
├── deptrac.yaml           # Configuration Deptrac
├── Makefile               # Commandes utiles
├── phpmd.xml              # Configuration PHPMD
├── phpstan.dist.neon      # Configuration PHPStan
└── phpunit.dist.xml       # Configuration PHPUnit
```

## 🐳 Services Docker

Le projet utilise Docker Compose pour l'environnement de développement :

- **database** : PostgreSQL 16 Alpine
- **fusionauth** : FusionAuth (authentification et gestion des utilisateurs)

## 📝 Commandes Make disponibles

- `make install` : Installation complète du projet
- `make start` : Démarrage des services Docker et du serveur Symfony
- `make load-data` : Exécution des migrations et chargement des fixtures
- `make tests [filter=...]` : Exécution des tests
- `make lint` : Correction automatique du style de code
- `make ci-check` : Vérification complète de la qualité de code

## 🔐 Sécurité

- Authentification JWT via FusionAuth
- Validation des entrées utilisateur
- Protection CSRF (via Symfony)
- Headers de sécurité configurés

## 📄 Licence

Proprietary

## 👥 Contribution

1. Créer une branche depuis `main`
2. Développer la fonctionnalité
3. S'assurer que tous les tests passent (`make tests`)
4. Vérifier la qualité de code (`make ci-check`)
5. Créer une pull request

## 🆘 Support

Pour toute question ou problème, veuillez ouvrir une issue sur le dépôt.

