# Guide d'installation et de lancement du projet

Ce projet utilise **Symfony + API Platform + JWT + Vue.js + Docker + Elasticsearch + Mercure + Mailhog**.  
Voici les étapes nécessaires pour le faire fonctionner sur une machine fraîchement clonée.

---

## 1. Prérequis

- [Docker Desktop](https://www.docker.com/) ou équivalent doit être installé et lancé.
- [Node.js](https://nodejs.org/) et `npm` installés (pour le frontend).
- [Composer](https://getcomposer.org/) installé (pour exécuter les commandes Symfony).

---

## 2. Démarrage des conteneurs

Depuis la racine du projet cloné :

```
docker compose up -d --build

Vérifier que les conteneurs sont bien en cours d'exécution :
docker ps

## 3. Installation des dépendances Backend
Entrer dans le conteneur Symfony :
docker compose exec backend 

Installer les dépendances PHP :
composer install

Créer la base de données et appliquer les migrations :
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate -n

Vider le cache :
php bin/console cache:clear

## 4. Installation des dépendances Frontend
Dans un nouveau terminal :
cd frontend
npm install
npm run dev   # pour développement
# ou npm run build pour production

## 5. Vérification des services externes
Elasticsearch : http://localhost:9200
Mercure : http://localhost:3000/.well-known/mercure
Mailhog : http://localhost:8025

## 6. Génération des clés JWT
Si les clés n'existent pas encore :

php bin/console lexik:jwt:generate-keypair
⚠️ La régénération des clés invalidera tous les anciens tokens — les utilisateurs devront se reconnecter.

## 7. Vérification finale
API disponible sur : http://localhost:8000/api
Frontend disponible sur : http://localhost:5173 (ou selon votre configuration)
Tester les endpoints avec curl ou Postman pour confirmer le bon fonctionnement.


Notes
En cas de modification du code, reconstruire les conteneurs avec :
docker compose up -d --build


Après un changement de schéma de base de données, exécuter à nouveau :
php bin/console doctrine:migrations:migrate -n
php bin/console cache:clear