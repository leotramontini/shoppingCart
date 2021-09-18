#!/usr/bin/env bash

echo "Up containers:\n"
make up

echo "Copy .env.example to .env\n"
cp .env.example .env

echo "Generate laravel keys: \n"
docker exec application su docker && php artisan key:generate