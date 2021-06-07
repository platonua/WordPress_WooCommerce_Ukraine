# Модуль WordPress WooCommerce для Украины

## Чеклист интеграции:
- [x] Установить модуль.
- [x] Передать тех поддержке PSP Platon  ссылку для коллбеков.
- [x] Провести оплату используя тестовые реквизиты.

## Установка:

* Установка производится из маркетплейса Wordpress: Plugins -> Добавить новый -> Выполнить поиск «Platon Pay WooCommerce» -> Установить.

* После успешной установки необходимо «Активировать» ваш плагин (Plugins -> Platon Pay WooCommerce -> Активировать).

* В боковом меню админ консоли выбираем «Platon Pay».

* Для дальнейших настроек Вам необходимо нажать кнопку «Включить».

* Заполнить поля «Секретный ключ» и «Пароль» — полученные у менеджера.

* Прописать URL https://secure.platononline.com/payment/auth

* Сохранить изменения.

## Иностранные валюты:
Готовые CMS модули PSP Platon по умолчанию поддерживают только оплату в UAH.

Если необходимы иностранные валюты необходимо провести правки модуля вашими программистами согласно раздела [документации] (https://platon.atlassian.net/wiki/spaces/docs/pages/1810235393).

## Ссылка для коллбеков:
https://ВАШ_САЙТ/?wc-api=WC_Gateway_Platononline

## Тестирование:
В целях тестирования используйте наши тестовые реквизиты.

| Номер карты  | Месяц / Год | CVV2 | Описание результата |
| :---:  | :---:  | :---:  | --- |
| 4111  1111  1111  1111 | 02 / 2022 | Любые три цифры | Не успешная оплата без 3DS проверки |
| 4111  1111  1111  1111 | 06 / 2022 | Любые три цифры | Не успешная оплата с 3DS проверкой |
| 4111  1111  1111  1111 | 01 / 2022 | Любые три цифры | Успешная оплата без 3DS проверки |
| 4111  1111  1111  1111 | 05 / 2022 | Любые три цифры | Успешная оплата с 3DS проверкой |
