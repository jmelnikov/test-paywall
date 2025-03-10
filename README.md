# Тестовое задание для компании PayWall

## Ограничения тестового выполнения

- Везде используется одинаковые имя пользователя и пароль: `paywall`
- Пароли для MySQL установлены в файле `docker/.env`, который загружен в репозиторий
- Для Redis пароль не установлен
- Порты в Docker открыты для доступа извне

## Запуск

1. Склонировать репозиторий
2. Перейти в папку docker, которая находится в корне репозитория
3. Выполнить команду `docker-compose up`
4. Установка зависимостей и миграция БД произойдет автоматически
5. Приложение будет доступно по адресу `http://localhost:8088`

## Использование

### Настройка языка

По умолчанию установлен русский язык.
Для изменения языка по умолчанию на английский, необходимо в файле `app/config/packages/translation.yaml` изменить
значение
параметра `default_locale` на `en`.

### Обработка запросов

У приложения есть два роута, оба принимают только POST-запросы:

1. `/test-json` - обрабатывает запрос, который был предоставлен в тестовом задании.
    - Формат запроса: `json`
    - Проверяет наличие необходимых полей в запросе
    - Выбрасывает исключения, если важные поля отсутствуют или не соответствуют формату

2. `/test-form` - обрабатывает запрос в стандартном формате.
    - Формат запроса: `x-www-form-urlencoded` или `form-data`
    - Принимает только два поля:
        - `user_id` - id пользователя в Telegram
        - `status` - статус платежа `success` или `error`
    - Также проверяет наличие обязательных полей и выкидывает исключения, если они отсутствуют
    - Сейчас валидация наличия полей происходит в ручном режиме. Можно использовать валидацию через библиотеку
      `symfony/validator`, но тогда придётся изменить формат запроса.
    - Ответы возвращает на русском языке.

### Отправка сообщений в Telegram

- Для отправки сообщений в Telegram необходимо в файле `app/.env` установить переменную окружения `TELEGRAM_BOT_TOKEN` с
  токеном бота.
- Отправка реализована через стандартный Http Client.
- Отправка сообщений происходит через очередь, чтобы не блокировать основной поток приложения.
- Задержка перед каждой отправкой 100мс (1/10 секунды), чтобы не превысить лимиты Telegram API. _Это работает, если
  воркер только один. Если бы воркеров было несколько, нужно было бы использовать DelayStamp() для контроля скорости
  отправки сообщений._
- В случае ошибки при отправке сообщения, ошибка выводится в консоль.
- Поддерживается форматирование текста Markdown.
- Чтобы отправлялись сообщения в Telegram, надо в файле `app/.env` добавить токен _до запуска контейнера с воркером_,
  либо перезапустить
  контейнер с воркером для того, чтобы он подцепил новый токен.
