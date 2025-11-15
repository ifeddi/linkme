#!/bin/bash

echo "🚀 Starting LinkMe application and testing..."

# Démarrer les conteneurs
echo "1. Starting Docker containers..."
make up

# Attendre que les conteneurs soient prêts
echo "2. Waiting for containers to be ready..."
sleep 10

# Initialiser la base de données
echo "3. Initializing database..."
make db-init

# Tester les endpoints
echo "4. Testing API endpoints..."
node debug-endpoints.js

echo "✅ Setup complete! You can now test the application."
echo "Frontend: http://localhost:5173"
echo "Backend: http://localhost:8000"
