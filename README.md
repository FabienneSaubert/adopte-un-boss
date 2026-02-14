# Adopte Un Boss – API

## 🎀 Présentation

**Adopte Un Boss** est une plateforme de mise en relation entre **candidats** et **recruteurs**, basée sur un système de **matching** entre profils, compétences et offres d’emploi.

Ce dépôt correspond à la **partie API** du projet.  
L’API expose l’ensemble des fonctionnalités nécessaires à la plateforme :
- gestion des candidats
- gestion des recruteurs
- gestion des entreprises
- gestion des compétences
- gestion des offres d’emploi
- gestion des candidatures
- gestion des demandes de contact

L’API est conçue pour être consommée par un client externe (application web ou mobile), par exemple :
- **Flutter**
- **React**
- ou toute autre application front-end

👉 Le front-end et l’API sont **complètement découplés**.

---

## 🛠️ Stack technique

- **PHP 8+**
- **Symfony**
- **Doctrine ORM**
- **MySQL / MariaDB**

---

## 🚀 Installation

### 1️⃣ Cloner le projet

#### 🔹 Pour un testeur (HTTPS)

```bash
git clone https://gitlab.com/floriangarciasoto/adopte-un-boss.git
cd adopte-un-boss
```

#### 🔹 Pour un développeur du projet (SSH)

```bash
git clone git@gitlab.com:floriangarciasoto/adopte-un-boss.git
cd adopte-un-boss
```

### 2️⃣ Sélection de la branche

Selon l’état du projet que vous souhaitez **tester** ou **développer**, il peut être nécessaire de changer de branche après le clonage.

Par convention :
- `main` correspond à la version stable
- `dev` correspond à la version de développement (branche la plus utilisée par les développeurs)

Pour lister les branches disponibles :

```bash
git branch -a
```

Pour se placer sur la branche de développement :

```bash
git checkout dev
```

Ou, si la branche n’existe pas encore en local :

```bash
git checkout -b dev origin/dev
```

---

### 3️⃣ Installer les dépendances PHP

Le projet utilise **Composer** pour gérer les dépendances PHP.

Après avoir cloné le dépôt et sélectionné la branche souhaitée, installez les dépendances avec la commande suivante :

```bash
composer install
````

Cette commande :

* lit le fichier **`composer.lock`**
* installe **exactement les versions** des dépendances définies pour le projet
* supprime ou met à jour les packages si nécessaire
* génère l’autoload des classes

👉 Le fichier `composer.lock` est la **source de vérité** pour les dépendances.

---

#### 🔀 Dépendance à la branche

Chaque branche du projet (`main`, `dev`, etc.) peut contenir :

* un `composer.json` différent
* et surtout un `composer.lock` différent

Par conséquent :

* après un changement de branche (`git checkout`)
* ou après un `git pull` mettant à jour `composer.json` / `composer.lock`

➡️ il est recommandé de faire `composer install`, afin de synchroniser l’environnement local avec l’état du projet sur la branche courante.

---

📌 **Bonnes pratiques** :

* utiliser `composer install` (et non `composer update`)
* ne jamais modifier manuellement `composer.lock`
* toujours relancer `composer install` après un pull modifiant les dépendances

---

Une fois les dépendances installées, vous pouvez configurer la base de données, la créer avec ses entités et lancer le projet.

---

### 4️⃣ Configurer la base de données

Le projet est fourni avec une **configuration par défaut** pour l’environnement de développement (`dev`) dans le fichier `.env`.

Par défaut, vous trouverez une ligne de ce type :

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/adopte_un_boss?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
```

⚠️ **Il ne faut pas modifier directement le fichier `.env`**.
Si votre configuration locale diffère, vous devez créer un fichier **`.env.local`**, qui surchargera automatiquement les valeurs.

---

#### 🔧 Création du fichier `.env.local`

À la racine du projet, copier le fichier `.env` pour créer le fichier `.env.local` :

```bash
cp .env .env.local
```

Puis modifiez **uniquement** la ligne `DATABASE_URL` dans `.env.local`.

---

#### 🧩 Détail des paramètres à configurer

```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/db_name?serverVersion=8.0"
```

| Élément         | Description                                            |
| --------------- | ------------------------------------------------------ |
| `user`          | Nom d’utilisateur MySQL (ex : `root`)                  |
| `password`      | Mot de passe MySQL (vide possible selon config locale) |
| `127.0.0.1`     | Adresse du serveur de base de données                  |
| `3306`          | Port MySQL (par défaut : 3306)                         |
| `db_name`       | Nom de la base de données                              |
| `serverVersion` | Version exacte du serveur MySQL                        |

---

#### 🔍 Comment trouver la version du serveur MySQL

La valeur `serverVersion` doit **correspondre à la version réelle de votre serveur MySQL**.

Vous pouvez la trouver :

* dans **phpMyAdmin** (page d’accueil → informations serveur)
* ou via la commande SQL :

```sql
SELECT VERSION();
```

Exemples de valeurs valides :

* `8.0`
* `8.0.34`
* `5.7`

👉 En cas de doute, utilisez la version majeure (`8.0` par exemple).

---

#### ✅ Exemple de configuration locale

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/adopte_un_boss?serverVersion=8.0"
```

---

📌 **Rappel important** :

* `.env` → configuration par défaut du projet
* `.env.local` → configuration locale personnelle (qui ne sera pas commit)

---

Une fois la base configurée, vous pouvez passer à la création et à la migration de la base de données.

---

### 5️⃣ Créer la base de données avec ses entités

Par précaution, si une base existe déjà :

```bash
php bin/console doctrine:database:drop --force
```
Créer la base de données :

```bash
php bin/console doctrine:database:create
```

Appliquer les migrations :

```bash
php bin/console doctrine:migrations:migrate
```

---

### 6️⃣ Créer la paire de clés permettant de signer les token JWT

```bash
php bin/console lexik:jwt:generate-keypair --overwrite
```

---

### 7️⃣ Lancer le serveur de développement

```bash
symfony server:start
```

ou :

```bash
php -S localhost:8000 -t public
```

L’API est alors accessible sur :

```
http://localhost:8000/api/<entity>
```

Remplacer `<entity>` par l'entité que vous souhaitez requêter (ex. `candidat`).

---

## 📌 Notes

* L’authentification (JWT, sécurité, middleware) sera ajoutée ultérieurement.
* Les échanges se font exclusivement en **JSON**.
* Le projet suit une architecture **API-first**.