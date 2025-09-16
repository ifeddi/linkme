#!/bin/bash
set -e

echo "🚀 Installing Symfony Backend..."
cd backend
if [ ! -f "composer.json" ]; then
  composer create-project symfony/skeleton .
  composer require api orm maker security lexik/jwt-authentication-bundle
else
  composer install
fi
cd ..

echo "🎨 Installing Vue Frontend..."
cd frontend
npm install
npm install axios pinia vue-router@4
cd ..
