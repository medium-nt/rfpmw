#!/bin/bash

set -e

if [ ! -f artisan ]; then
    echo -e "${RED}Ошибка: скрипт нужно запускать из корня Laravel-проекта.${RESET}"
    exit 1
fi

PHP="/usr/local/bin/php8.4"

RED="\e[31m"
GREEN="\e[32m"
YELLOW="\e[33m"
BLUE="\e[34m"
MAGENTA="\e[35m"
CYAN="\e[36m"
RESET="\e[0m"

block() {
    echo -e "${CYAN}\n===== $1 =====${RESET}"
}

echo
echo -e "${GREEN}=========== НАЧАЛО ДЕПЛОЯ ===========${RESET}"
echo -e "${BLUE}Текущая директория:${RESET} $(pwd)"
echo -e "${BLUE}Время:${RESET} $(date)"

block "ОБНОВЛЕНИЕ КОДА (git pull)"
git pull
echo -e "${CYAN}======================================${RESET}"

block "УСТАНОВКА ЗАВИСИМОСТЕЙ (composer)"

if [ ! -f composer.phar ]; then
    echo -e "${RED}Не найден composer.phar${RESET}"
    exit 1
fi

$PHP composer.phar install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-dev
echo -e "${CYAN}========================================${RESET}"

block "ПЕРЕСБОРКА КЕШЕЙ LARAVEL"
$PHP artisan optimize:clear
$PHP artisan optimize
echo -e "${CYAN}=================================${RESET}"

echo -e "${GREEN}\n====== ДЕПЛОЙ ЗАВЕРШЁН ======${RESET}"
echo -e "${BLUE}Время:${RESET} $(date)"
echo -e "${GREEN}=============================${RESET}"
echo
