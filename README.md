== uLogin - виджет авторизации через социальные сети ==
Donate link: http://ulogin.ru/
Tags: ulogin, login, social, authorization
Requires at least: 2.x.x
Tested up to: 3.x.x
Stable tag: 1.0
License: GPL3
Форма авторизации uLogin через социальные сети. Улучшенный аналог loginza.

== Description ==

uLogin — это инструмент, который позволяет пользователям получить единый доступ к различным Интернет-сервисам без необходимости повторной регистрации,
а владельцам сайтов — получить дополнительный приток клиентов из социальных сетей и популярных порталов (Google, Яндекс, Mail.ru, ВКонтакте, Facebook и др.)

== Installation ==
	1) Скопировать все файлы и папки находящиеся в папке /upload в архиве в корень phpFox.
	2) Импортировать продукт uLogin административной панеле(AdminCP).
  	3) Cнизить уровень безопасности на low для получения token от ulogin.ru. 
	   Для этого в AdminCP->Settings->ManageSettings->ServerSettings->CSRF Protection Level выставить в low.

== Changelog ==
  - Добавлена загрузка профильных фото.
  - Добавлена страница синхронизации/привязки профилей uLogin. Страница находится в Account settings->uLogin account settings.
    Для синхронизации и привязки к текущей учетной записью нужно залогиниться через социальную сеть на странице uLogin account settings.
    В качестве опций синхронизации можно указать поля из списка, в этом случае указанные поля будут обновленны из выбранной социальной сети.
    После синхронизации можно использовать выбранную социальную сеть для входа. Процесс можно повторить для привязки дополнительных социальных сетей или для обновления полей.