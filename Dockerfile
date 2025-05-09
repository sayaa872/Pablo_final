# Utiliser l'image PHP officielle
FROM php:8.0-cli

# Définir le répertoire de travail
WORKDIR /app

# Copier tous les fichiers dans le conteneur
COPY . .

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Exposer le port
EXPOSE 8000

# Commande de démarrage
CMD ["php", "-S", "0.0.0.0:8000"]